<template>
  <div>
    <div class="panel panel-primary">
      <div class="panel-heading">
        {{ $t('Processing settings') }}
      </div>
      <div class="panel-body">
        <div class="form-group">
          <label for="" class="label label-default">{{ $t('Field delimiter') }}</label>
          <input type="text" :placeholder="$t('Field delimiter')" v-model="csvFieldDelimiter" class="form-control">
        </div>

        <div class="form-group">
          <label class="label label-default">{{ $t('Encoding') }}</label>
          <select class="form-control" v-model="encoding">
            <option v-for="(encodingName, idx) in encodings" :key="idx" :value="encodingName">{{ encodingName }}</option>
          </select>
        </div>

        <div class="form-group">
          <div class="checkbox">
            <label>
              <input type="checkbox" v-model="dumpParentCategories"> {{ $t('Dump parent categories') }}
            </label>
          </div>
        </div>

        <div class="form-group">
          <label class="label label-default">{{ $t('Category level delimiter') }}</label>
          <input type="text" class="form-control" v-model="categoriesNestingDelimiter">
        </div>

        <div class="form-group">
          <label class="label label-default">{{ $t('Categories delimiter') }}</label>
          <input type="text" class="form-control" v-model="categoriesDelimiter">
        </div>

        <div class="form-group">
          <label class="label label-default">{{ $t('Export with status') }}</label>
          <select class="form-control" v-model="exportWithStatus">
            <option value="-1">{{ $t('Any status') }}</option>
            <option v-for="(status,statusIdx) in statuses" :value="status.id" :key="statusIdx">{{ status.name }}</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapVuexModels } from 'vuex-models'
import { createNamespacedHelpers } from 'vuex'

const { mapGetters } = createNamespacedHelpers('exporter')

export default {
  computed: {
    ...mapVuexModels([
      'dumpParentCategories',
      'categoriesNestingDelimiter',
      'categoriesDelimiter',
      'csvFieldDelimiter',
      'encoding',
      'status',
      'exportWithStatus'
    ], 'exporter'),
    ...mapGetters([
      'encodings',
      'statuses'
    ])
  }
}
</script>
