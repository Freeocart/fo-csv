<template>
<div>
  <csv-column-name-binder v-model="value" :items="csvFields"></csv-column-name-binder>
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
    ])
  },
  watch: {
    value (newV) {
      this.$emit('change', newV)
    }
  }
}
</script>
