<template>
  <div class="progress-container">
    <div class="progress" :style="{ width: width }"></div>
    <span class="progress-counter" :style="{ color: progressValue < 50 ? '#000' : '#fff'}">{{ progress.width }}%</span>
  </div>
</template>

<script>
export default {
  props: {
    progress: {
      default: {
        current: 0,
        total: 0
      }
    }
  },
  computed: {
    progressValue () {
      return ((this.progress.current / this.progress.total) * 100) || 0
    },
    width () {
      return `${this.progressValue}%`
    }
  }
}
</script>

<style scoped>
.progress-container {
  height: 30px;
  background-color: #fff;
  border: 1px solid #5ab8e8;
  border-radius: 20px;
  overflow: hidden;
  text-align: center;
  position: relative;
}
.progress-counter {
  position: absolute;
  top: 50%;
  left: 50%;
  color: #fff;
  font-size: 16px;
  margin-top: -10px;
  margin-left: -10px;
}
.progress {
  background-color: #5cb7e7;
  background: -moz-linear-gradient(45deg, rgba(0,189,247,1) 0%, rgba(0,173,237,1) 31%, rgba(0,187,239,1) 46%, rgba(0,173,237,1) 63%, rgba(0,134,244,1) 100%); /* FF3.6-15 */
  background: -webkit-linear-gradient(45deg, rgba(0,189,247,1) 0%,rgba(0,173,237,1) 31%,rgba(0,187,239,1) 46%,rgba(0,173,237,1) 63%,rgba(0,134,244,1) 100%); /* Chrome10-25,Safari5.1-6 */
  background: linear-gradient(45deg, rgba(0,189,247,1) 0%,rgba(0,173,237,1) 31%,rgba(0,187,239,1) 46%,rgba(0,173,237,1) 63%,rgba(0,134,244,1) 100%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
  filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#00bdf7', endColorstr='#0086f4',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */
  box-shadow: 0 0 10px 3px rgba(0, 149, 255, 0.52);
  height: 100%;
  border-radius: 0;
  width: 0;
}
</style>
