<template>
  <div class="panel panel-primary">
    <div class="panel-heading">
      {{ $t('Images settings') }}
    </div>
    <div class="panel-body">
      <div class="form-group">
        <label class="label label-default">{{ $t('Export images mode') }}</label>
        <select class="form-control" v-model="exportImagesMode">
          <option :value="null">{{ $t('Not selected') }}</option>
          <option>{{ $t('Full URL') }}</option>
          <option>{{ $t('File name') }}</option>
        </select>
      </div>

      <div class="form-group">
        <div class="checkbox">
          <label>
            <input type="checkbox" v-model="createImagesZIP"> {{ $t('Create images ZIP') }}
          </label>
        </div>
      </div>

      <div class="form-group">
        <label class="label label-default">{{ $t('Images list delimiter') }}</label>
        <input type="text" class="form-control" v-model="galleryImagesDelimiter">
      </div>
    </div>
  </div>
</template>

<script>
import { mapVuexModels } from '@/helpers'

export default {
  computed: {
    ...mapVuexModels([
      'exportImagesMode',
      'createImagesZIP',
      'galleryImagesDelimiter'
    ], 'exporter')
  }
}
</script>
