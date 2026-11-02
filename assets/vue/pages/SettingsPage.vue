<script setup>
import { useI18n } from 'vue-i18n';
import ChipGroup from '../components/ui/ChipGroup.vue';
import FormField from '../components/ui/FormField.vue';
import PageHeader from '../components/ui/PageHeader.vue';
import { chooseLanguage, currentLanguage, LANGUAGES } from '../i18n/locale.js';

const { t } = useI18n();

const languages = LANGUAGES.map(({ value, label }) => ({ value, label }));
</script>

<template>
    <div class="page">
        <PageHeader :title="t('settings.title')" />
        <FormField :label="t('settings.language')" as="div">
            <ChipGroup :model-value="currentLanguage()" :options="languages" :label="t('settings.language')" @update:model-value="chooseLanguage" />
        </FormField>
    </div>
</template>
