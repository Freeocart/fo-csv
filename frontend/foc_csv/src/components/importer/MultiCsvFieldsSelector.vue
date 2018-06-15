<template>
  <div>
    <csv-column-name-binder v-model="value" :items="options"></csv-column-name-binder>
  </div>
</template>

<script>
import CsvColumnNameBinder from './CsvColumnNameBinder'

export default {
  components: {
    CsvColumnNameBinder
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
