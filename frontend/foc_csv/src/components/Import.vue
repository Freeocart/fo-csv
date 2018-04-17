<template>
  <div class="hello">
    <div>
      <h1>{{ msg }}</h1>
      <!-- <div v-if="!importingCsvProgress"> -->
        <div>
          <label for="">Профиль</label>
          <select v-model="currentProfileName">
            <option v-for="(profile, idx) in profiles" :key="idx">{{ idx }}</option>
          </select>
        </div>

        <button @click.prevent="savingProfile = true">Сохранить профиль как</button>

        <div v-if="savingProfile">
          <input type="text" placeholder="Название профиля" ref="newProfileName" :value="currentProfileName">
          <button @click.prevent="saveNewProfile($refs.newProfileName.value)">Сохранить профиль</button>
        </div>

        <div>
          <label for="">Магазин</label>
          <select v-model="store">
            <option v-for="(store, idx) in stores" :key="idx" :value="store.id">{{ store.name }}</option>
          </select>
        </div>

        <div>
          <label for="">Язык</label>
          <select v-model="language">
            <option v-for="(lang, idx) in languages" :key="idx" :value="lang.id">{{ lang.name }}</option>
          </select>
        </div>

        <div>
          <label for="">Ключевое поле (по нему идет сличение)</label>
          <select v-model="keyField">
            <option v-for="(field, idx) in keyFields" :key="idx">{{ field }}</option>
          </select>
        </div>

        <div>
          <label for="">Кодировка</label>
          <select v-model="encoding">
            <option v-for="(encodingName, idx) in encodings" :key="idx" :value="encodingName">{{ encodingName }}</option>
          </select>
        </div>

        <div>
          <label for="">Разделитель полей</label>
          <input type="text" placeholder="Разделитель полей" v-model="csvFieldDelimiter">
        </div>

        <div>
          <label for="">Режим импорта</label>
          <select v-model="importMode">
            <option value="onlyUpdate">Только обновить существующие</option>
            <option value="onlyAdd">Добавить как новые</option>
            <option value="updateCreate">Обновить существующие и добавить новые</option>
            <option value="addIfNotFound">Только добавить отсутствующие</option>
            <option value="removeByList">Удалить совпавшие</option>
            <option value="removeOthers">Удалить несовпавшие</option>
          </select>
        </div>

        <div>
          <label for="">Разделитель вложенности категорий</label>
          <input type="text" v-model="categoryLevelDelimiter">
        </div>
        <div>
          <label for="">Разделитель категорий</label>
          <input type="text" v-model="categoryDelimiter">
        </div>
        <div>
          <label for="">Заполнить родительские категории</label>
          <input type="checkbox" v-model="fillParentCategories">
        </div>

        <div>
          <label for="">Пропустить первую строку
            <input type="checkbox" v-model="skipFirstLine">
          </label>
        </div>

        <div>
          <label for="">Сопоставление полей</label>
          <div v-for="(field, idx) in csvFields" :key="idx">
            <span>{{ field }}</span>
            <db-fields-select :selected="currentProfile.bindings[idx]" :data="dbFields" @changed="bindDBToCsvField([ $event, idx ])"></db-fields-select>
          </div>
        </div>

        <div>
          <label for="">Сколько обновлять записей за раз</label>
          <input type="text" v-model="processAtStepNum">
        </div>

        <csv-file-upload></csv-file-upload>

        <div>
          <label for="">
            Подкачивать картинки по URL (все ссылки начинающиеся на http/https)
            <input type="checkbox" v-model="downloadImages">
          </label>
        </div>

        <div>
          ZIP Архив картинок
          <images-zip-upload></images-zip-upload>
        </div>

        <div>
          <label for="">
            Разделитель в поле изображений
            <input type="text" v-model="csvImageFieldDelimiter">
          </label>
        </div>

        <div>
          <label for="">
            Установить главное изображение из галереи в случае отсутствия
          </label>
          <input type="checkbox" v-model="previewFromGallery">
        </div>

        <div>
          <label for="">
            Удалить изображения из галереи перед импортом
          </label>
          <input type="checkbox" v-model="clearGalleryBeforeImport">
        </div>

        <div>
          <label for="">Режим установки изображений</label>
          <select v-model="imagesImportMode">
            <option value="add">Добавить загруженные</option>
            <option value="skip">Не добавлять если галерея не пуста</option>
          </select>
        </div>

        <div>
          <button @click.prevent="submitImportData">Погнали!</button>
        </div>
      <!-- </div> -->

      <!-- <div v-else> -->
        <p>importing...</p>
        <import-progress :progress="csvImportProgress"></import-progress>
      <!-- </div> -->
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
