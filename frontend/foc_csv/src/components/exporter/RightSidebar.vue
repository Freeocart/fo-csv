<template>
  <div>
    <div class="panel panel-primary">
      <div class="panel-heading">
        {{ $t('Category control') }}
      </div>
      <div class="panel-body">
        <div class="form-group">
          <label class="label label-default">{{ $t('Dump parent categories') }}</label>
          <input type="checkbox" class="form-control" v-model="dumpParentCategories">
        </div>

        <div class="form-group">
          <label class="label label-default">{{ $t('Categories nesting delimiter') }}</label>
          <input type="text" class="form-control" v-model="categoriesNestingDelimiter">
        </div>

        <div class="form-group">
          <label class="label label-default">{{ $t('Categories delimiter') }}</label>
          <input type="text" class="form-control" v-model="categoriesDelimiter">
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapVuexModels } from '@/helpers'

export default {
  computed: {
    ...mapVuexModels([
      'dumpParentCategories',
      'categoriesNestingDelimiter',
      'categoriesDelimiter'
    ], 'exporter')
  }
}
</script>
