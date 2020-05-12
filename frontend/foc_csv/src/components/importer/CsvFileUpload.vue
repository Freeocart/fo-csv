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
        <button class="btn btn-success" :disabled="!csvFileRef || readInProgress" @click.prevent="updateFromFile()"><i :class="{'fa-spin': readInProgress, 'fa fa-refresh': true}"></i> {{ $t('Re-read CSV') }}</button>
      </div>
    </div>
    <div class="col-md-12 alert alert-danger" v-if="error">
      <strong>{{ $t('Something wrong with your file! Please choose another.') }}</strong>
    </div>
  </div>
</template>

<script>
import { FirstLineReader, parseCsvHeaders, isFileSeemsLikeCsv } from '@/helpers'

import { createNamespacedHelpers } from 'vuex'
import { mapVuexModels } from 'vuex-models'

const { mapActions, mapGetters } = createNamespacedHelpers('importer')

export default {
  data () {
    return {
      fileSelected: false,
      error: false,
      readInProgress: false
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

      if (file !== null && isFileSeemsLikeCsv(file)) {
        const reader = new FirstLineReader()
        reader.on('line', (line) => {
          const headers = parseCsvHeaders(line, this.csvFieldDelimiter)
          this.setCsvFieldNames(headers)
          this.readInProgress = false
        })
        reader.on('error', () => {
          this.error = true
          this.readInProgress = false
        })

        reader.read(file, this.encoding, skipToHeaders)
      }
      else {
        this.readInProgress = false
        this.error = true
      }
    },
    updateFromFile () {
      this.error = false
      if (this.csvFileRef.files && this.csvFileRef.files.length > 0) {
        this.readBlob(this.csvFileRef.files[0])
        this.readInProgress = true
      }
    },
    fileChange (event) {
      this.error = false
      if (event.srcElement.files.length > 0) {
        this.setCsvFileRef(this.$refs.fileRef)
        this.readBlob(event.srcElement.files[0])
        this.readInProgress = true
      }
    },
    ...mapActions([
      'setCsvFieldNames',
      'setCsvFileRef'
    ])
  }
}
</script>
