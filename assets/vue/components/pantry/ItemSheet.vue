<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useApi } from '../../composables/useApi.js';
import { useToast } from '../../composables/useToast.js';
import { intlLocale } from '../../i18n/locale.js';
import { KIND_ICONS, KINDS, LOCATION_ICONS, LOCATIONS, UNITS } from '../../pantry/locations.js';
import { defaultLowAt, MAX_TWELFTHS, TWELFTHS_PER_UNIT } from '../../pantry/quantity.js';
import BaseButton from '../ui/BaseButton.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import BaseSwitch from '../ui/BaseSwitch.vue';
import BottomSheet from '../ui/BottomSheet.vue';
import ChipGroup from '../ui/ChipGroup.vue';
import ConfirmButton from '../ui/ConfirmButton.vue';
import FormField from '../ui/FormField.vue';
import FractionChips from './FractionChips.vue';
import QuantityStepper from './QuantityStepper.vue';

const props = defineProps({
    item: { type: Object, default: null },
    initialName: { type: String, default: '' },
    initialLocation: { type: String, default: 'pantry' },
    initialKind: { type: String, default: 'ingredient' },
    initialStock: { type: Number, default: TWELFTHS_PER_UNIT },
    allowAnother: { type: Boolean, default: true },
    items: { type: Array, default: () => [] },
    ingredientsOnly: { type: Boolean, default: false },
});
const open = defineModel('open', { type: Boolean, required: true });
const emit = defineEmits(['saved']);

const { t } = useI18n();
const api = useApi();
const toast = useToast();

const name = ref('');
const kind = ref('ingredient');
const location = ref('pantry');
const unit = ref('piece');
const manual = ref(false);
const lowAt = ref(defaultLowAt('piece'));
const alert = ref(true);
const ALL_MONTHS = Array.from({ length: 12 }, (_, index) => index + 1);
const months = ref(ALL_MONTHS);
const variantOf = ref(null);
const whole = ref(props.initialStock);
const fraction = ref(0);
const nameError = ref(null);
const saving = ref(false);
const nameInput = ref(null);

const editing = computed(() => props.item !== null);
const household = computed(() => kind.value === 'household');
const kindOptions = computed(() => KINDS.map((value) => ({ value, label: t(`pantry.kind.${value}`), icon: KIND_ICONS[value] })));
const capitalize = (text) => text.charAt(0).toLocaleUpperCase() + text.slice(1);
const locationOptions = computed(() => LOCATIONS.map((value) => ({ value, label: t(`pantry.location.${value}`), icon: LOCATION_ICONS[value] })));
const unitOptions = computed(() => UNITS.map((value) => ({ value, label: capitalize(t(`units.${value}.one`)) })));
const lowAtStep = computed(() => defaultLowAt(unit.value));
const monthOptions = computed(() => {
    const format = new Intl.DateTimeFormat(intlLocale(), { month: 'short', timeZone: 'UTC' });
    return ALL_MONTHS.map((value) => ({ value, label: format.format(Date.UTC(2026, value - 1, 1)) }));
});
const hasVariants = computed(() => editing.value && props.item.variantOf === null && props.items.some((candidate) => candidate.variantOf === props.item.id));
const variantOptions = computed(() => [
    { value: null, label: t('pantry.sheet.noVariant') },
    ...props.items.filter((candidate) => candidate.variantOf === null && candidate.id !== props.item?.id && candidate.kind !== 'household').map((candidate) => ({ value: candidate.id, label: candidate.name })),
]);

watch(open, (isOpen) => {
    if (!isOpen) return;
    nameError.value = null;
    if (props.item) {
        ({ name: name.value, kind: kind.value, location: location.value, unit: unit.value, manual: manual.value, lowAt: lowAt.value, alert: alert.value, months: months.value, variantOf: variantOf.value } = props.item);
        return;
    }
    name.value = props.initialName;
    kind.value = props.ingredientsOnly ? 'ingredient' : props.initialKind;
    location.value = kind.value === 'household' ? 'home' : props.initialLocation;
    unit.value = 'piece';
    manual.value = false;
    lowAt.value = defaultLowAt('piece');
    alert.value = true;
    months.value = ALL_MONTHS;
    variantOf.value = null;
    resetStock();
});

function resetStock() {
    whole.value = props.initialStock;
    fraction.value = 0;
}

function changeKind(next) {
    if (!editing.value) location.value = next === 'household' ? 'home' : props.initialLocation;
    kind.value = next;
}

function changeUnit(next) {
    if (lowAt.value === defaultLowAt(unit.value)) lowAt.value = defaultLowAt(next);
    unit.value = next;
}

function focusName(event) {
    if (!editing.value && nameInput.value) {
        event.preventDefault();
        nameInput.value.focus();
    }
}

async function save(another) {
    saving.value = true;
    nameError.value = null;
    const body = { name: name.value, kind: kind.value, location: location.value, unit: unit.value, manual: manual.value || household.value, lowAt: lowAt.value, alert: alert.value, months: household.value ? ALL_MONTHS : months.value, variantOf: household.value ? null : variantOf.value, ...(editing.value ? {} : { stock: whole.value + fraction.value }) };
    try {
        if (editing.value) {
            emit('saved', await api.put(`/api/items/${props.item.id}`, body));
        } else {
            emit('saved', await api.post('/api/items', body));
            toast.success(t('pantry.sheet.added', { name: body.name.trim() }));
        }
        if (another) {
            name.value = '';
            manual.value = false;
            lowAt.value = defaultLowAt(unit.value);
            alert.value = true;
            months.value = ALL_MONTHS;
            variantOf.value = null;
            resetStock();
            await nextTick();
            nameInput.value?.focus();
        } else {
            open.value = false;
        }
    } catch (error) {
        nameError.value = error.fieldErrors?.name ?? (error.status === 422 ? error.message : null);
        if (nameError.value === null) toast.error(error.message);
    } finally {
        saving.value = false;
    }
}

async function remove() {
    const removed = await toast.attempt(() => api.del(`/api/items/${props.item.id}`));
    if (removed) open.value = false;
}
</script>

<template>
    <BottomSheet v-model:open="open" :title="editing ? t('pantry.sheet.editTitle') : t(ingredientsOnly ? 'pantry.sheet.createIngredientTitle' : 'pantry.sheet.createTitle')" @open-auto-focus="focusName">
        <form id="item-form" class="item-sheet" @submit.prevent="save(false)">
            <FormField :label="t('pantry.sheet.name')" :error="nameError">
                <input ref="nameInput" v-model="name" type="text" maxlength="60" autocomplete="off" />
            </FormField>
            <FormField v-if="!ingredientsOnly" :label="t('pantry.sheet.kind')" as="div">
                <ChipGroup :model-value="kind" :options="kindOptions" :label="t('pantry.sheet.kind')" data-test="kind" @update:model-value="changeKind" />
            </FormField>
            <FormField :label="t('pantry.sheet.location')" as="div">
                <ChipGroup v-model="location" :options="locationOptions" :label="t('pantry.sheet.location')" />
            </FormField>
            <FormField :label="t('pantry.sheet.unit')">
                <BaseSelect :model-value="unit" :options="unitOptions" @update:model-value="changeUnit" />
            </FormField>
            <template v-if="!editing">
                <FormField :label="t('pantry.sheet.stock')" as="div">
                    <QuantityStepper v-model="whole" :label="t('pantry.sheet.stockWhole')" :decrease-label="t('pantry.sheet.stockLess')" :increase-label="t('pantry.sheet.stockMore')" :step="TWELFTHS_PER_UNIT" :max="MAX_TWELFTHS - TWELFTHS_PER_UNIT" />
                </FormField>
                <FormField :label="t('pantry.sheet.stockFraction')" as="div">
                    <FractionChips v-model="fraction" :label="t('pantry.sheet.stockFraction')" />
                </FormField>
            </template>
            <FormField v-if="!household" :label="t('pantry.sheet.manual')" :hint="t('pantry.sheet.manualHint')" as="div">
                <BaseSwitch v-model="manual" :label="t('pantry.sheet.manual')" />
            </FormField>
            <FormField :label="t('pantry.sheet.alert')" :hint="t('pantry.sheet.alertHint')" as="div">
                <BaseSwitch v-model="alert" :label="t('pantry.sheet.alert')" data-test="alert" />
            </FormField>
            <FormField v-if="alert" :label="t('pantry.sheet.lowAt')" as="div">
                <QuantityStepper v-model="lowAt" :label="t('pantry.sheet.lowAt')" :decrease-label="t('pantry.sheet.less')" :increase-label="t('pantry.sheet.more')" :step="lowAtStep" :max="MAX_TWELFTHS" />
            </FormField>
            <FormField v-if="!household" :label="t('pantry.sheet.months')" :hint="t('pantry.sheet.monthsHint')" as="div">
                <ChipGroup v-model="months" multiple allow-none :options="monthOptions" :label="t('pantry.sheet.months')" data-test="months" />
                <div class="item-sheet__months">
                    <BaseButton variant="ghost" :disabled="months.length === 12" @click="months = ALL_MONTHS">{{ t('pantry.sheet.allYear') }}</BaseButton>
                    <BaseButton variant="ghost" :disabled="months.length === 0" data-test="never" @click="months = []">{{ t('pantry.sheet.never') }}</BaseButton>
                </div>
            </FormField>
            <FormField v-if="items.length > 0 && !hasVariants && !household" :label="t('pantry.sheet.variantOf')" :hint="t('pantry.sheet.variantOfHint')">
                <BaseSelect v-model="variantOf" :options="variantOptions" data-test="variant-of" />
            </FormField>
        </form>
        <template #footer>
            <span v-if="editing" class="item-sheet__delete">
                <ConfirmButton :label="t('pantry.sheet.delete')" :title="t('pantry.sheet.deleteTitle', { name: item.name })" :message="t('pantry.sheet.deleteMessage')" variant="danger" @confirm="remove" />
            </span>
            <BaseButton v-if="!editing && allowAnother" variant="secondary" :disabled="saving" @click="save(true)">{{ t('pantry.sheet.saveAndAdd') }}</BaseButton>
            <BaseButton type="submit" form="item-form" :loading="saving">{{ t('pantry.sheet.save') }}</BaseButton>
        </template>
    </BottomSheet>
</template>

<style scoped>
.item-sheet { display: flex; flex-direction: column; gap: var(--space-4); }
.item-sheet__months { display: flex; flex-wrap: wrap; gap: var(--space-2); margin-top: var(--space-2); }
.item-sheet__delete { margin-right: auto; }
</style>
