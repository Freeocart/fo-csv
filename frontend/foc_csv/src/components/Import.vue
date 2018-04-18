<template>
  <div class="foc-csv-settings-panel">
    <template v-if="importingCsvProgress">
      <div class="panel panel-primary">
        <div class="panel-heading">
          Импорт данных в процессе
        </div>
        <div class="panel-body">
          <import-progress :progress="csvImportProgress"></import-progress>
        </div>
      </div>
    </template>
    <div class="row">
      <div class="col-md-12">
        <div class="form-group text-right">
          <button @click.prevent="submitImportData" class="btn btn-primary btn-lg"><i class="fa fa-rocket"></i> Погнали!</button>
        </div>
      </div>
      <div class="col-md-4">
        <div class="panel panel-primary">
          <div class="panel-heading">
            Основное
          </div>
          <div class="panel-body">
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
          </div>
        </div>

        <div class="panel panel-primary">
          <div class="panel-heading">
            Управление изображениями
          </div>

          <div class="panel-body">
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
                Подкачивать картинки по URL
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

      <div class="col-md-5">
        <div class="panel panel-primary">
          <div class="panel-heading">
            Управление полями
          </div>
          <div class="panel-body">

            <div class="form-group">
              <label for="" class="label label-default">CSV файл</label>
              <csv-file-upload></csv-file-upload>
            </div>

            <div class="form-group">
              <label for="" class="label label-default">Сколько обновлять записей за раз</label>
              <input type="text" v-model="processAtStepNum" class="form-control">
            </div>

            <div class="form-group">
              <label for="" class="label label-default">Пропустить первую строку</label>
              <input type="checkbox" v-model="skipFirstLine">
            </div>

            <div class="form-group">
              <label for="" class="label label-danger">Ключевое поле (по нему идет сличение)</label>
              <select v-model="keyField" class="form-control">
                <option v-for="(field, idx) in keyFields" :key="idx">{{ field }}</option>
              </select>
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
      </div>

      <div class="col-md-3">
        <div class="panel panel-primary">
          <div class="panel-heading">
            Настройки сличения
          </div>

          <div class="panel-body">
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
              <label for="" class="label label-default">Удалить производителей перед импортом</label>
              <input type="checkbox" class="form-control" v-model="removeManufacturersBeforeImport">
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
            <div class="form-group">
              <label for="" class="label label-default">Удалить символы из поля категорий</label>
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
import axios from 'axios'
import DbFieldsSelect from '@/components/DbFieldsSelect'
import CsvFileUpload from '@/components/CsvFileUpload'
import ImagesZipUpload from '@/components/ImagesZipUpload'
import ImportProgress from '@/components/ImportProgress'
import StatusRewrites from '@/components/StatusRewrites'

export default {
  components: {
    DbFieldsSelect,
    CsvFileUpload,
    ImagesZipUpload,
    ImportProgress,
    StatusRewrites
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
