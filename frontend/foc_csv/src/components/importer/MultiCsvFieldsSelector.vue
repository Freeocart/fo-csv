<template>
  <div>
    <multiselect
      :options="preparedOptions"
      v-model="value"
      :multiple="true"
      :custom-label="csvFieldLabel"
    ></multiselect>
  </div>
</template>

<script>
import Multiselect from 'vue-multiselect'

export default {
  components: {
    Multiselect
  },
  props: {
    options: {
      type: Array,
      default: () => ([])
    },
    value: {
      type: Array,
      default: () => ([])
    }
  },
  methods: {
    csvFieldLabel (index) {
      return this.options[index]
    }
  },
  computed: {
    preparedOptions () {
      return Object.keys(this.options)
    }
  },
  watch: {
    value (newV) {
      this.$emit('input', newV)
    }
  }
}
</script>

<style type="text/scss">
@import 'vue-multiselect/dist/vue-multiselect.min.css';
</style>
