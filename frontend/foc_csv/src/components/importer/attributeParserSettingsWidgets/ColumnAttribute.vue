<template>
<div>
  <csv-column-name-binder v-model="_value" :items="csvFields"></csv-column-name-binder>
</div>
</template>

<script>
import { createNamespacedHelpers } from 'vuex'
import CsvColumnNameBinder from '../CsvColumnNameBinder'

const { mapGetters } = createNamespacedHelpers('importer')

export default {
  components: {
    CsvColumnNameBinder
  },
  props: {
    value: {
      default: ''
    }
  },
  computed: {
    ...mapGetters([
      'csvFields'
    ]),
    _value: {
      get () {
        return this.value
      },
      set (val) {
        this.$emit('change', val)
      }
    }
  }
}
</script>
