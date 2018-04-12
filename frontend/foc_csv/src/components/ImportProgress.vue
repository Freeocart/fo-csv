<template>
  <div class="progress" :style="{ width: width }">

  </div>
</template>

<script>
export default {
  props: {
    progress: {
      default: {}
    }
  },
  computed: {
    width () {
      console.log(this.progress.current, this.progress.total)
      return ((this.progress.current / this.progress.total) * 100) + '%'
    }
  }
}
</script>

<style scoped>
.progress {
  height: 30px;
  background-color: blue;
  text-align: center;
}
</style>
