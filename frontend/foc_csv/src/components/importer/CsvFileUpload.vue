<template>
  <div class="row">
    <div class="col-md-8">
      <input ref="fileRef" type="file" @change="fileChange($event)" accept=".csv">
    </div>
    <div class="col-md-4">
      <button class="btn btn-success" :disabled="!csvFileRef" @click.prevent="updateFromFile()"><i class="fa fa-refresh"></i> {{ $t('Re-read file info') }}</button>
    </div>
  </div>
</template>

<script>
import { FirstLineReader, parseCsvHeaders } from '@/helpers'

import { createNamespacedHelpers } from 'vuex'
import { mapVuexModels } from 'vuex-models'

const { mapActions, mapGetters } = createNamespacedHelpers('importer')

export default {
  data () {
    return {
      fileSelected: false
    }
  },
  computed: {
    ...mapGetters([
      'csvFileRef'
    ]),
    ...mapVuexModels([
      'skipLines',
      'csvFieldDelimiter',
      'csvHeadersLineNumber',
      'csvWithoutHeaders',
      'encoding'
    ], 'importer')
  },
  methods: {
    readBlob (file) {
      let skipToHeaders = 0

      if (!this.csvWithoutHeaders) {
        skipToHeaders = parseInt(this.csvHeadersLineNumber)
      }
      else {
        skipToHeaders = parseInt(this.skipLines) + 1
      }

      if (file !== null && file.type === 'text/csv') {
        let reader = new FirstLineReader()
        reader.on('line', (line) => {
          let headers = parseCsvHeaders(line, this.csvFieldDelimiter)
          this.setCsvFieldNames(headers)
        })

        reader.read(file, this.encoding, skipToHeaders)
      }
      else {
        console.log(file)
      }
    },
    updateFromFile () {
      if (this.csvFileRef.files && this.csvFileRef.files.length > 0) {
        this.readBlob(this.csvFileRef.files[0])
      }
    },
    fileChange (event) {
      if (event.srcElement.files.length > 0) {
        this.setCsvFileRef(this.$refs.fileRef)
        this.readBlob(event.srcElement.files[0])
      }
    },
    ...mapActions([
      'setCsvFieldNames',
      'setCsvFileRef'
    ])
  }
}
</script>
