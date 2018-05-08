<template>
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
    <div class="form-group">
      <label class="label label-default">{{ $t('Default status') }}</label>
      <select class="form-control" v-model="defaultStatus">
        <option :value="undefined">{{ $t('Not selected') }}</option>
        <option v-for="(status, idx) in statuses" :key="idx" :value="status.id">{{ status.name }}</option>
      </select>
    </div>

    <status-rewrites
      :statuses="statuses"
      :rules="statusRewrites"
      @statusRewriteChange="setStatusRewriteRule($event)"
    ></status-rewrites>

    <hr>

    <!-- Статусы наличия -->
    <div class="form-group">
      <label class="label label-default">{{ $t('Default stock status') }}</label>
      <select class="form-control" v-model="defaultStockStatus">
        <option :value="undefined">{{ $t('Not selected') }}</option>
        <option v-for="(status, idx) in stock_statuses" :key="idx" :value="status.id">{{ status.name }}</option>
      </select>
    </div>

    <status-rewrites
      :statuses="stock_statuses"
      :rules="stockStatusRewrites"
      @statusRewriteChange="setStockStatusRewriteRule($event)"
    ></status-rewrites>

    <!-- Пропуск строк -->
    <line-skip-settings
      :csvFields="csvFields"
      v-model="skipLineOnEmptyFields"
    ></line-skip-settings>
  </div>
</div>
</template>

<script>
import StatusRewrites from './StatusRewrites'
import LineSkipSettings from './LineSkipSettings'

import { mapVuexModels } from '@/helpers'
import { createNamespacedHelpers } from 'vuex'

const { mapGetters, mapActions } = createNamespacedHelpers('importer')

export default {
  components: {
    StatusRewrites,
    LineSkipSettings
  },
  computed: {
    ...mapGetters([
      'encodings',
      'statuses',
      'statusRewrites',
      'stock_statuses',
      'stockStatusRewrites'
    ]),
    ...mapVuexModels([
      'csvFieldDelimiter',
      'encoding',
      'removeManufacturersBeforeImport',
      'importMode',
      'fillParentCategories',
      'categoryDelimiter',
      'categoryLevelDelimiter',
      'removeCharsFromCategory',
      'defaultStatus',
      'defaultStockStatus',
      'csvFields',
      'skipLineOnEmptyFields'
    ], 'importer')
  },
  methods: {
    ...mapActions([
      'setStatusRewriteRule',
      'setStockStatusRewriteRule'
    ])
  }
}
</script>
