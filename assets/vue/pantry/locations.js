import { PhHouse, PhJar, PhSnowflake, PhSquaresFour, PhThermometerCold } from '@phosphor-icons/vue';

export const LOCATIONS = ['fridge', 'freezer', 'pantry', 'household'];

export const UNITS = ['piece', 'bottle', 'jar', 'pack', 'bag', 'box', 'carton', 'can', 'tub'];

export const LOCATION_ICONS = {
    fridge: PhThermometerCold,
    freezer: PhSnowflake,
    pantry: PhJar,
    household: PhHouse,
    all: PhSquaresFour,
};
