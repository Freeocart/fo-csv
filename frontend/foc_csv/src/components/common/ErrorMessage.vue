<template>
<div v-if="errors > 0">
  <div class="alert alert-danger" role="alert">
    <slot><p>There is errors while doing job!</p></slot>
    <p>{{ $t('Errors count') }} - <strong>{{ errors }}</strong></p>
  </div>
</div>
</template>

<script>
export default {
  props: {
    errors: {
      type: Number,
      default: 0
    }
  }
}
</script>
