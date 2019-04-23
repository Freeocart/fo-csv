<template>
  <input type="text" class="form-control" :value="value" @change="$emit('change', $event.target.value)">
</template>

<script>
export default {
  props: {
    value: {
      default: ''
    }
  }
}
</script>
