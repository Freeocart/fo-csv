<template>
  <div>
    <input ref="fileRef" type="file" @change="readBlob($event)" accept=".csv">
  </div>
</template>

<script>
import { FirstLineReader, parseCsvHeaders } from '@/helpers'

import { createNamespacedHelpers } from 'vuex'

const { mapActions, mapGetters } = createNamespacedHelpers('importer')

export default {
  computed: {
    ...mapGetters([
      'encoding',
      'csvFieldDelimiter'
    ])
  },
  methods: {
    readBlob (event) {
      let file = null

      if (event.srcElement.files.length > 0) {
        file = event.srcElement.files[0]
      }

      if (file !== null && file.type === 'text/csv') {
        let reader = new FirstLineReader()
        reader.on('line', (line) => {
          let headers = parseCsvHeaders(line, this.csvFieldDelimiter)
          this.setCsvFieldNames(headers)
          this.setCsvFileRef(this.$refs.fileRef)
        })

        reader.read(file, this.encoding)
      }
    },
    ...mapActions([
      'setCsvFieldNames',
      'setCsvFileRef'
    ])
  }
}
</script>
