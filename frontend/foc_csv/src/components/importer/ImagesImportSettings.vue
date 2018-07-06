<template>
<div>
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
    <div class="checkbox">
      <label>
        <input type="checkbox" v-model="previewFromGallery"> {{ $t('If no preview - set it from gallery') }}
      </label>
    </div>
    <div class="checkbox">
      <label>
        <input type="checkbox" v-model="clearGalleryBeforeImport"> {{ $t('Clear gallery before import') }}
      </label>
    </div>
    <div class="checkbox">
      <label>
        <input type="checkbox" v-model="downloadImages"> {{ $t('Download images with URL') }}
      </label>
    </div>
  </div>

  <div class="form-group">
    <label for="" class="label label-default">{{ $t('Images import mode') }}</label>
    <select v-model="imagesImportMode" class="form-control">
      <option value="add">{{ $t('Add images') }}</option>
      <option value="skip">{{ $t('Skip if gallery has images') }}</option>
    </select>
  </div>
</div>
</template>

<script>
import ImagesZipUpload from './ImagesZipUpload'
import { mapVuexModels } from 'vuex-models'

export default {
  components: {
    ImagesZipUpload
  },
  computed: {
    ...mapVuexModels([
      'csvImageFieldDelimiter',
      'imagesImportMode',
      'downloadImages',
      'previewFromGallery',
      'clearGalleryBeforeImport'
    ])
  }
}
</script>
