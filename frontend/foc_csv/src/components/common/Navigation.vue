<template>
  <ul class="nav nav-tabs">
    <router-link to="import" tag="li" active-class="active">
      <a>{{ $t('Import') }}</a>
    </router-link>
    <router-link to="export" tag="li" active-class="active">
      <a>{{ $t('Export') }}</a>
    </router-link>
    <router-link to="util" tag="li" active-class="active">
      <a>{{ $t('Util') }}</a>
    </router-link>
    <router-link to="info" tag="li" active-class="active">
      <a>{{ $t('Info') }}</a>
    </router-link>
  </ul>
</template>
