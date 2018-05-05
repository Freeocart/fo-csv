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
    <status-rewrites
      :statuses="statuses"
      :rules="statusRewrites"
      @statusRewriteChange="setStatusRewriteRule($event)"
    ></status-rewrites>

    <!-- Статусы наличия -->
    <status-rewrites
      :statuses="stock_statuses"
      :rules="stockStatusRewrites"
      @statusRewriteChange="setStockStatusRewriteRule($event)"
    ></status-rewrites>
  </div>
</div>
</template>

<script>
import StatusRewrites from './StatusRewrites'
import { mapVuexModels } from '@/helpers'
import { createNamespacedHelpers } from 'vuex'

const { mapGetters, mapActions } = createNamespacedHelpers('importer')

export default {
  components: {
    StatusRewrites
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
      'removeCharsFromCategory'
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
