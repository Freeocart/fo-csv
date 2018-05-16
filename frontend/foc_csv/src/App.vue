<template>
  <div>
    <navigation></navigation>
    <router-view/>
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
