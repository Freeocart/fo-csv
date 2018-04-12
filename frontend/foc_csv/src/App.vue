<template>
  <div>
    {{ appConfig }}
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
