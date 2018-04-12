<template>
  <div>
    <input ref="fileRef" type="file" @change="readBlob($event)" accept=".csv">
  </div>
</template>

<script>
import { FirstLineReader, parseCsvHeaders } from '@/helpers'

export default {
  methods: {
    readBlob (event) {
      let file = null

      if (event.srcElement.files.length > 0) {
        file = event.srcElement.files[0]
      }

      if (file !== null && file.type === 'text/csv') {
        let reader = new FirstLineReader()
        reader.on('line', (line) => {
          let headers = parseCsvHeaders(line)
          this.$store.dispatch('setCsvFieldNames', headers)
          this.$store.dispatch('setCsvFileRef', this.$refs.fileRef)
        })

        reader.read(file)
      }
    }
  }
}
</script>
