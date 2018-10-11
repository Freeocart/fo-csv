<template>
  <div>
    <navigation></navigation>
    <keep-alive include="import,export">
      <router-view/>
    </keep-alive>
  </div>
</template>

<script>
import Navigation from '@/components/common/Navigation'

export default {
  name: 'App',
  components: {
    Navigation
  },
  data () {
    return {
      appConfig: this.$root.$options.AppConfig
    }
  }
}
</script>
