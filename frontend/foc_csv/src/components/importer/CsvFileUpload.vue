<template>
  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label for="" class="label label-default">{{ $t('CSV file') }}</label>
        <input ref="fileRef" type="file" @change="fileChange($event)" accept=".csv">
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <button class="btn btn-success" :disabled="!csvFileRef" @click.prevent="updateFromFile()"><i class="fa fa-refresh"></i> {{ $t('Re-read CSV') }}</button>
      </div>
    </div>
    <div class="col-md-12 alert alert-danger" v-if="error">
      <strong>{{ $t('Something wrong with your file! Please choose another.') }}</strong>
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
      fileSelected: false,
      error: false
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
        reader.on('error', () => {
          this.error = true
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
        this.error = false
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
