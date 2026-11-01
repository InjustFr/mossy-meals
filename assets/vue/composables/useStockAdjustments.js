import { reactive } from 'vue';
import { MAX_TWELFTHS } from '../pantry/quantity.js';
import { useApi } from './useApi.js';
import { useToast } from './useToast.js';

const IDLE_BEFORE_SENDING = 400;

export function useStockAdjustments() {
    const api = useApi();
    const toast = useToast();
    const unsent = reactive({});
    const inFlight = reactive({});
    const timers = new Map();

    const pendingOf = (id) => (unsent[id] ?? 0) + (inFlight[id] ?? 0);

    function view(item) {
        const stock = item.stock + pendingOf(item.id);
        return { ...item, stock, out: stock === 0, runningLow: stock <= item.lowAt };
    }

    async function send(id) {
        timers.delete(id);
        const by = unsent[id] ?? 0;
        delete unsent[id];
        if (by === 0) return;
        inFlight[id] = (inFlight[id] ?? 0) + by;
        try {
            await api.post(`/api/items/${id}/adjust`, { by });
        } catch (error) {
            toast.error(error.message);
        } finally {
            inFlight[id] -= by;
            if (inFlight[id] === 0) delete inFlight[id];
        }
    }

    function move(id, shown, to) {
        unsent[id] = (unsent[id] ?? 0) + Math.min(MAX_TWELFTHS, Math.max(0, to)) - shown;
        clearTimeout(timers.get(id));
        timers.set(id, setTimeout(() => send(id), IDLE_BEFORE_SENDING));
    }

    return { view, move };
}
