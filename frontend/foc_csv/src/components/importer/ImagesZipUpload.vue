<template>
<div>
  <input ref="imagesZipRef" type="file" @change="setRef($event)" accept=".zip">
</div>
</template>

<script>
import { createNamespacedHelpers } from 'vuex'

const { mapActions } = createNamespacedHelpers('importer')

export default {
  methods: {
    setRef (event) {
      this.setImagesZipRef(this.$refs.imagesZipRef)
    },
    ...mapActions([
      'setImagesZipRef'
    ])
  }
}
</script>
