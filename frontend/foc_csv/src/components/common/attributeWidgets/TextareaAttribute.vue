<template>
  <textarea type="text" class="form-control" :value="value" @change="$emit('change', $event.target.value)">
  </textarea>
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
