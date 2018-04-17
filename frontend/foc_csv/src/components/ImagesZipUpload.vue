<template>
<div>
  <input ref="imagesZipRef" type="file" @change="setRef($event)" accept=".zip">
</div>
</template>

<script>
export default {
  methods: {
    setRef (event) {
      this.$store.dispatch('setImagesZipRef', this.$refs.imagesZipRef)
    }
  }
}
</script>
