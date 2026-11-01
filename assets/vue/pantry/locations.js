import { PhBroom, PhCarrot, PhHouse, PhJar, PhSnowflake, PhSquaresFour, PhThermometerCold } from '@phosphor-icons/vue';

export const KINDS = ['ingredient', 'household'];

export const KIND_ICONS = {
    ingredient: PhCarrot,
    household: PhBroom,
};

export const LOCATIONS = ['fridge', 'freezer', 'pantry', 'home'];

export const UNITS = ['piece', 'bottle', 'jar', 'pack', 'bag', 'box', 'carton', 'can', 'tub'];

export const LOCATION_ICONS = {
    fridge: PhThermometerCold,
    freezer: PhSnowflake,
    pantry: PhJar,
    home: PhHouse,
    all: PhSquaresFour,
};
