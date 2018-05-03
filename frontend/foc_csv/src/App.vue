<template>
  <div>
    <ul class="nav nav-tabs">
      <router-link to="import" tag="li" active-class="active">
        <a>Import</a>
      </router-link>
      <router-link to="export" tag="li" active-class="active">
        <a>Export (soon)</a>
      </router-link>
      <router-link to="util" tag="li" active-class="active">
        <a>Util</a>
      </router-link>
    </ul>

    <router-view/>
  </div>
</template>

<script>
export default {
  name: 'App',
  data () {
    return {
      appConfig: this.$root.$options.AppConfig
    }
  }
}
</script>
