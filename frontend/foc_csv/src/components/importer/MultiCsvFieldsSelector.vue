<template>
  <div>
    <csv-column-name-binder v-model="_value" :items="options"></csv-column-name-binder>
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
    },
    _value: {
      get () {
        return this.value
      },
      set (val) {
        console.log(val)
        this.$emit('input', val)
      }
    }
  }
}
</script>
