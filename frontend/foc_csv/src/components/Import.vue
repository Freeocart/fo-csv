<template>
  <div class="foc-csv-settings-panel">
    <template v-if="importingCsvProgress">
      <div class="well">
        <h3>importing...</h3>
        <import-progress :progress="csvImportProgress"></import-progress>
      </div>
    </template>
    <div class="row" v-else>
      <div class="col-md-12">
        <div class="form-group text-right">
          <button @click.prevent="submitImportData" class="btn btn-primary btn-lg"><i class="fa fa-rocket"></i> Погнали!</button>
        </div>
      </div>
      <div class="col-md-4">
        <div class="well">
          <h3>Основное</h3>

          <div class="form-group">
            <label for="" class="label label-default">Профиль</label>
            <select v-model="currentProfileName" class="form-control">
              <option v-for="(profile, idx) in profiles" :key="idx">{{ idx }}</option>
            </select>
          </div>

          <button @click.prevent="savingProfile = true" class="btn btn-default"><i class="fa fa-save"></i> Сохранить профиль как</button>

          <div v-if="savingProfile" class="input-group">
            <input type="text" placeholder="Название профиля" ref="newProfileName" :value="currentProfileName" class="form-control">
            <span class="input-group-btn">
              <button @click.prevent="saveNewProfile($refs.newProfileName.value)" class="btn btn-success"><i class="fa fa-check"></i> Сохранить профиль</button>
            </span>
          </div>

          <div class="form-group">
            <label for="" class="label label-default">Магазин</label>
            <select v-model="store" class="form-control">
              <option v-for="(store, idx) in stores" :key="idx" :value="store.id">{{ store.name }}</option>
            </select>
          </div>

          <div class="form-group">
            <label for="" class="label label-default">Язык</label>
            <select v-model="language" class="form-control">
              <option v-for="(lang, idx) in languages" :key="idx" :value="lang.id">{{ lang.name }}</option>
            </select>
          </div>

          <div class="form-group">
            <label for="" class="label label-default">CSV файл</label>
            <csv-file-upload></csv-file-upload>
          </div>

          <div class="form-group">
            <label for="" class="label label-default">Ключевое поле (по нему идет сличение)</label>
            <select v-model="keyField" class="form-control">
              <option v-for="(field, idx) in keyFields" :key="idx">{{ field }}</option>
            </select>
          </div>

          <div class="form-group">
            <label for="" class="label label-default">Сколько обновлять записей за раз</label>
            <input type="text" v-model="processAtStepNum" class="form-control">
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="well">

          <div class="form-group">
            <label for="" class="label label-default">Пропустить первую строку</label>
            <input type="checkbox" v-model="skipFirstLine">
          </div>

          <label for="" class="label label-default">Сопоставление полей</label>
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>CSV Поле</th>
                <th>DB Поле</th>
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

      <div class="col-md-4">
        <div class="well">
          <h3>Настройки сличения</h3>

          <div class="form-group">
            <label for="" class="label label-default">Разделитель полей</label>
            <input type="text" placeholder="Разделитель полей" v-model="csvFieldDelimiter" class="form-control">
          </div>

          <div class="form-group">
            <label for="" class="label label-default">Кодировка</label>
            <select v-model="encoding" class="form-control">
              <option v-for="(encodingName, idx) in encodings" :key="idx" :value="encodingName">{{ encodingName }}</option>
            </select>
          </div>

          <div class="form-group">
            <label for="" class="label label-default">Режим импорта</label>
            <select v-model="importMode" class="form-control">
              <option value="onlyUpdate">Только обновить существующие</option>
              <option value="onlyAdd">Добавить как новые</option>
              <option value="updateCreate">Обновить существующие и добавить новые</option>
              <option value="addIfNotFound">Только добавить отсутствующие</option>
              <option value="removeByList">Удалить совпавшие</option>
              <option value="removeOthers">Удалить несовпавшие</option>
            </select>
          </div>

          <div class="form-group">
            <label for="" class="label label-default">Разделитель вложенности категорий</label>
            <input type="text" v-model="categoryLevelDelimiter" class="form-control">
          </div>
          <div class="form-group">
            <label for="" class="label label-default">Разделитель категорий</label>
            <input type="text" v-model="categoryDelimiter" class="form-control">
          </div>
          <div class="form-group">
            <label for="" class="label label-default">Заполнить родительские категории</label>
            <input type="checkbox" v-model="fillParentCategories" class="form-control">
          </div>

          <hr>

          <h3>Управление изображениями</h3>

          <div class="form-group">
            <label class="label label-default">ZIP Архив картинок</label>
            <images-zip-upload></images-zip-upload>
          </div>

          <div class="form-group">
            <label for="" class="label label-default">
              Разделитель в поле изображений
            </label>
            <input type="text" v-model="csvImageFieldDelimiter" class="form-control">
          </div>

          <div class="form-group">
            <label for="" class="label label-default">
              Установить главное изображение из галереи в случае отсутствия
            </label>
            <input type="checkbox" v-model="previewFromGallery" class="form-control">
          </div>

          <div class="form-group">
            <label for="" class="label label-default">
              Удалить изображения из галереи перед импортом
            </label>
            <input type="checkbox" v-model="clearGalleryBeforeImport" class="form-control">
          </div>

          <div class="form-group">
            <label for="" class="label label-default">
              Подкачивать картинки по URL (все ссылки начинающиеся на http/https)
            </label>

            <input type="checkbox" v-model="downloadImages" class="form-control">
          </div>

          <div class="form-group">
            <label for="" class="label label-default">Режим установки изображений</label>
            <select v-model="imagesImportMode" class="form-control">
              <option value="add">Добавить загруженные</option>
              <option value="skip">Не добавлять если галерея не пуста</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'
import { submitData, validateProfile, mapVuexModels } from '@/helpers'
import axios from 'axios'
import DbFieldsSelect from '@/components/DbFieldsSelect'
import CsvFileUpload from '@/components/CsvFileUpload'
import ImagesZipUpload from '@/components/ImagesZipUpload'
import ImportProgress from '@/components/ImportProgress'

export default {
  components: {
    DbFieldsSelect,
    CsvFileUpload,
    ImagesZipUpload,
    ImportProgress
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
      'fillParentCategories'
    ])
  },
  methods: {
    async submitImportPart ({ importUrl, key, position }) {
      try {
        let data = {
          key,
          position,
          profile: this.$store.getters.profile
        }
        let response = await axios.post(decodeURIComponent(importUrl), data)

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
          let response = await submitData(this.$store.actionUrl('import'), data)

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
