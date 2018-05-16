<template>
<div class="form-group">
  <button class="btn btn-default" @click.prevent="showState = !showState">
    <slot>Show/hide</slot>
  </button>
  <textarea :value="JSON.stringify(value)" class="form-control" v-if="showState"></textarea>
</div>
</template>

<script>
export default {
  props: {
    value: {
      required: true
    },
    show: {
      default: false
    }
  },
  data () {
    let showState = this.show || false
    return {
      showState
    }
  }
}
</script>
