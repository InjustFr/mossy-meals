<script setup>
import { computed, reactive, ref } from 'vue';
import { PhCookingPot, PhPlus } from '@phosphor-icons/vue';
import { useI18n } from 'vue-i18n';
import MealSheet from '../components/cooking/MealSheet.vue';
import PrepSheet from '../components/cooking/PrepSheet.vue';
import RecipeCard from '../components/cooking/RecipeCard.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import PageHeader from '../components/ui/PageHeader.vue';
import PageSection from '../components/ui/PageSection.vue';
import { useApi } from '../composables/useApi.js';
import { linesFromRecipe } from '../cooking/lines.js';

const { t } = useI18n();
const api = useApi();

const recipes = ref([]);
api.load('/api/recipes', recipes);

const mealSheet = reactive({ open: false, heading: null, submitLabel: null, title: '', lines: [] });
const prepSheet = reactive({ open: false, recipeId: null });

const meals = computed(() => recipes.value.filter((recipe) => recipe.makes === 'meal' && recipe.makeable));
const preps = computed(() => recipes.value.filter((recipe) => recipe.makes === 'prep' && recipe.makeable));
const later = computed(() => recipes.value.filter((recipe) => !recipe.makeable && recipe.missing.length === 0));
const prep = computed(() => recipes.value.find((recipe) => recipe.id === prepSheet.recipeId) ?? null);

function logMeal() {
    Object.assign(mealSheet, { open: true, heading: null, submitLabel: null, title: '', lines: [] });
}

function cook(recipe) {
    Object.assign(mealSheet, { open: true, heading: t('meals.sheet.cookTitle', { name: recipe.name }), submitLabel: t('cook.cookIt'), title: recipe.name, lines: linesFromRecipe(recipe) });
}

function make(recipe) {
    Object.assign(prepSheet, { open: true, recipeId: recipe.id });
}
</script>

<template>
    <div class="page">
        <PageHeader :title="t('nav.cook')">
            <BaseButton data-test="log-meal" @click="logMeal"><PhPlus size="1.125rem" weight="bold" aria-hidden="true" />{{ t('cook.logMeal') }}</BaseButton>
        </PageHeader>

        <EmptyState v-if="recipes.length === 0" :icon="PhCookingPot" :title="t('cook.empty.title')" :hint="t('cook.empty.hint')">
            <BaseButton to="/recipes/new">{{ t('cook.empty.action') }}</BaseButton>
        </EmptyState>
        <EmptyState v-else-if="meals.length === 0 && preps.length === 0 && later.length === 0" :icon="PhCookingPot" :title="t('cook.noneReady.title')" :hint="t('cook.noneReady.hint')">
            <BaseButton to="/recipes" variant="secondary">{{ t('cook.noneReady.action') }}</BaseButton>
        </EmptyState>

        <PageSection v-if="meals.length > 0" :title="t('cook.ready')" :count="meals.length">
            <ul class="cook__list">
                <RecipeCard v-for="recipe in meals" :key="recipe.id" :recipe="recipe" :action-label="t('cook.cookIt')" @act="cook(recipe)" />
            </ul>
        </PageSection>

        <PageSection v-if="preps.length > 0" :title="t('cook.preps')" :count="preps.length">
            <ul class="cook__list">
                <RecipeCard v-for="recipe in preps" :key="recipe.id" :recipe="recipe" :action-label="t('cook.makeIt')" @act="make(recipe)" />
            </ul>
        </PageSection>

        <PageSection v-if="later.length > 0" :title="t('cook.later')" :count="later.length">
            <ul class="cook__list">
                <RecipeCard v-for="recipe in later" :key="recipe.id" :recipe="recipe" :action-label="t('cook.prepFirst')" @act="make(recipe.preps[0])" />
            </ul>
        </PageSection>

        <MealSheet v-model:open="mealSheet.open" :heading="mealSheet.heading" :submit-label="mealSheet.submitLabel" :title="mealSheet.title" :lines="mealSheet.lines" />
        <PrepSheet v-model:open="prepSheet.open" :recipe="prep" />
    </div>
</template>

<style scoped>
.cook__list { display: flex; flex-direction: column; gap: var(--space-3); margin: 0; padding: 0; list-style: none; }
</style>
