<template>
  <div class="foc-csv-settings-panel">
    <template v-if="importingCsvProgress">
      <div class="panel panel-primary">
        <div class="panel-heading">
          {{ $t('Import in progress') }}
        </div>
        <div class="panel-body">
          <import-progress :progress="csvImportProgress"></import-progress>
        </div>
      </div>
    </template>
    <div class="row">
      <div class="col-md-12">
        <div class="form-group text-right">
          <button @click.prevent="submitImportData" class="btn btn-warning btn-lg"><i class="fa fa-rocket"></i> {{ $t('Start import!') }}</button>
        </div>
      </div>
      <div class="col-md-4">
        <div class="panel panel-primary">
          <div class="panel-heading">
            {{ $t('Main settings') }}
          </div>
          <div class="panel-body">
            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Profile') }}</label>
              <select v-model="currentProfileName" class="form-control">
                <option v-for="(profile, idx) in profiles" :key="idx">{{ idx }}</option>
              </select>
            </div>

            <button @click.prevent="savingProfile = true" class="btn btn-default"><i class="fa fa-save"></i> {{ $t('Save profile as') }}</button>

            <div v-if="savingProfile" class="input-group">
              <input type="text" placeholder="Название профиля" ref="newProfileName" :value="currentProfileName" class="form-control">
              <span class="input-group-btn">
                <button @click.prevent="saveNewProfile($refs.newProfileName.value)" class="btn btn-success"><i class="fa fa-check"></i> {{ $t('Save profile') }}</button>
              </span>
            </div>

            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Store') }}</label>
              <select v-model="store" class="form-control">
                <option v-for="(store, idx) in stores" :key="idx" :value="store.id">{{ store.name }}</option>
              </select>
            </div>

            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Language') }}</label>
              <select v-model="language" class="form-control">
                <option v-for="(lang, idx) in languages" :key="idx" :value="lang.id">{{ lang.name }}</option>
              </select>
            </div>
          </div>
        </div>

        <div class="panel panel-primary">
          <div class="panel-heading">
            {{ $t('Images settings') }}
          </div>

          <div class="panel-body">
            <div class="form-group">
              <label class="label label-default">{{ $t('Images ZIP file') }}</label>
              <images-zip-upload></images-zip-upload>
            </div>

            <div class="form-group">
              <label for="" class="label label-default">
                {{ $t('Images list delimiter') }}
              </label>
              <input type="text" v-model="csvImageFieldDelimiter" class="form-control">
            </div>

            <div class="form-group">
              <label for="" class="label label-default">
                {{ $t('If no preview - set it from gallery') }}
              </label>
              <input type="checkbox" v-model="previewFromGallery" class="form-control">
            </div>

            <div class="form-group">
              <label for="" class="label label-default">
                {{ $t('Clear gallery before import') }}
              </label>
              <input type="checkbox" v-model="clearGalleryBeforeImport" class="form-control">
            </div>

            <div class="form-group">
              <label for="" class="label label-default">
                {{ $t('Download images with URL') }}
              </label>

              <input type="checkbox" v-model="downloadImages" class="form-control">
            </div>

            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Images import mode') }}</label>
              <select v-model="imagesImportMode" class="form-control">
                <option value="add">{{ $t('Add images') }}</option>
                <option value="skip">{{ $t('Skip if gallery has images') }}</option>
              </select>
            </div>
          </div>
        </div>

        <div class="panel panel-primary">
          <div class="panel-heading">
            {{ $t('Attributes import') }}
          </div>

          <div class="panel-body">

            <attributes-parser></attributes-parser>

          </div>
        </div>
      </div>

      <div class="col-md-5">
        <div class="panel panel-primary">
          <div class="panel-heading">
            {{ $t('Fields settings') }}
          </div>
          <div class="panel-body">

            <div class="form-group">
              <label for="" class="label label-default">{{ $t('CSV file') }}</label>
              <csv-file-upload></csv-file-upload>
            </div>

            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Process lines per query') }}</label>
              <input type="text" v-model="processAtStepNum" class="form-control">
            </div>

            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Skip first line') }}</label>
              <input type="checkbox" v-model="skipFirstLine">
            </div>

            <div class="form-group">
              <label for="" class="label label-danger">{{ $t('Key field') }}</label>
              <select v-model="keyField" class="form-control">
                <option v-for="(field, idx) in keyFields" :key="idx">{{ field }}</option>
              </select>
            </div>

            <label for="" class="label label-default">{{ $t('Fields matching') }}</label>
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>{{ $t('CSV field') }}</th>
                  <th>{{ $t('DB field') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(field, idx) in csvFields" :key="idx">
                  <td>
                    <span>{{ field }}</span>
                  </td>
                  <td>
                    <db-fields-select :selected="currentProfile.bindings[idx]" :data="dbFields" @changed="bindDBToCsvField([ $event, idx ])"></db-fields-select>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="panel panel-primary">
          <div class="panel-heading">
            {{ $t('Processing settings') }}
          </div>

          <div class="panel-body">
            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Field delimiter') }}</label>
              <input type="text" placeholder="Разделитель полей" v-model="csvFieldDelimiter" class="form-control">
            </div>

            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Encoding') }}</label>
              <select v-model="encoding" class="form-control">
                <option v-for="(encodingName, idx) in encodings" :key="idx" :value="encodingName">{{ encodingName }}</option>
              </select>
            </div>

            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Clear manufacturers before import') }}</label>
              <input type="checkbox" class="form-control" v-model="removeManufacturersBeforeImport">
            </div>

            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Import mode') }}</label>
              <select v-model="importMode" class="form-control">
                <option value="onlyUpdate">{{ $t('Only update existing') }}</option>
                <option value="onlyAdd">{{ $t('Force add all as new') }}</option>
                <option value="updateCreate">{{ $t('Update existing and add new') }}</option>
                <option value="addIfNotFound">{{ $t('Only add missing as new') }}</option>
                <option value="removeByList">{{ $t('Remove all matched') }}</option>
                <option value="removeOthers">{{ $t('Remove all unmatched') }}</option>
              </select>
            </div>

            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Category level delimiter') }}</label>
              <input type="text" v-model="categoryLevelDelimiter" class="form-control">
            </div>
            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Categories delimiter') }}</label>
              <input type="text" v-model="categoryDelimiter" class="form-control">
            </div>
            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Fill parent categories') }}</label>
              <input type="checkbox" v-model="fillParentCategories" class="form-control">
            </div>
            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Remove chars from category fields') }}</label>
              <input type="text" v-model="removeCharsFromCategory" class="form-control">
            </div>

            <!-- Статусы продукта -->
            <status-rewrites
              :statuses="$store.state.data.statuses"
              :rules="$store.getters.statusRewrites"
              @statusRewriteChange="setStatusRewriteRule($event)"
            ></status-rewrites>

            <!-- Статусы наличия -->
            <status-rewrites
              :statuses="$store.state.data.stock_statuses"
              :rules="$store.getters.stockStatusRewrites"
              @statusRewriteChange="setStockStatusRewriteRule($event)"
            ></status-rewrites>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'
import { submitData, validateProfile, mapVuexModels } from '@/helpers'
import { IMPORT_URL } from '@/urls'

import DbFieldsSelect from '@/components/DbFieldsSelect'
import CsvFileUpload from '@/components/CsvFileUpload'
import ImagesZipUpload from '@/components/ImagesZipUpload'
import ImportProgress from '@/components/ImportProgress'
import StatusRewrites from '@/components/StatusRewrites'
import AttributesParser from '@/components/AttributesParser'

export default {
  components: {
    DbFieldsSelect,
    CsvFileUpload,
    ImagesZipUpload,
    ImportProgress,
    StatusRewrites,
    AttributesParser
  },
  data () {
    return {
      msg: 'Import',
      newProfileName: '',
      savingProfile: false,
      importingCsvProgress: false,
      csvImportProgress: {
        current: 0,
        total: 0
      }
    }
  },
  computed: {
    ...mapGetters([
      'dbFields',
      'csvFields',
      'encodings',
      'profiles',
      'keyFields',
      'currentProfile',
      'stores',
      'languages'
    ]),
    ...mapVuexModels([
      'processAtStepNum',
      'skipFirstLine',
      'currentProfileName',
      'categoryDelimiter',
      'categoryLevelDelimiter',
      'csvFieldDelimiter',
      'encoding',
      'downloadImages',
      'importMode',
      'csvImageFieldDelimiter',
      'keyField',
      'imagesImportMode',
      'previewFromGallery',
      'clearGalleryBeforeImport',
      'store',
      'language',
      'fillParentCategories',
      'removeCharsFromCategory',
      'removeManufacturersBeforeImport'
    ])
  },
  methods: {
    setStatusRewriteRule (rule) {
      this.$store.dispatch('setStatusRewriteRule', rule)
    },
    setStockStatusRewriteRule (rule) {
      this.$store.dispatch('setStockStatusRewriteRule', rule)
    },
    async submitImportPart ({ importUrl, key, position }) {
      try {
        let data = {
          key,
          position,
          profile: this.$store.getters.profile
        }
        let response = await this.$http.post(decodeURIComponent(importUrl), data)

        position = response.data.message.position
        this.csvImportProgress.current = position

        if (position < this.csvImportProgress.total) {
          this.submitImportPart({ importUrl, key, position })
        }
        else {
          this.importingCsvProgress = false
        }
      }
      catch (e) {
        console.error(e)
      }
    },
    async submitImportData () {
      let data = this.$store.getters.submittableData

      if (validateProfile(data.profile)) {
        try {
          let response = await submitData(this.$store.actionUrl(IMPORT_URL), data)

          if (response.data.status === 'ok') {
            this.csvImportProgress.total = response.data.message.csvTotal
            this.importingCsvProgress = true
            this.submitImportPart(response.data.message)
          }
        }
        catch (e) {
          alert('Ошибка при отправке!')
          console.error(e)
        }
      }
      else {
        alert('Error in profile!')
      }
    },
    ...mapActions([
      'bindDBToCsvField',
      'setCurrentProfile',
      'saveNewProfile'
    ])
  }
}
</script>
