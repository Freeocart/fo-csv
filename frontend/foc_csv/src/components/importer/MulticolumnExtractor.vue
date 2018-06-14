<template>
<div>
  <label class="label label-default">{{ $t('Multicolumn fields settings') }}</label>
  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>{{ $t('CSV field') }}</th>
        <th>{{ $t('Preprocess value template') }}</th>
        <th>{{ $t('DB field') }}</th>
        <th>{{ $t('Control') }}</th>
      </tr>
    </thead>
    <tbody v-if="csvFields && csvFields.length > 0">
      <tr v-for="(field, idx) in multicolumnFields" :key="idx">
        <td>
          <multi-csv-fields-selector :options="csvFields" v-model="field.csvFields"></multi-csv-fields-selector>
        </td>
        <td>
          <textarea :value="undefined" v-model="field.valueTemplate" class="form-control" :placeholder="$t('Preprocess value template')"></textarea>
        </td>
        <td>
          <db-fields-select :data="dbFields" v-model="field.dbField"></db-fields-select>
        </td>
        <td>
          <button class="btn btn-danger" @click.prevent="deleteMulticolumnField(idx)">
            <i class="fa fa-times"></i> {{ $t('Delete') }}
          </button>
        </td>
      </tr>
      <tr>
        <td colspan="3">&nbsp; {{ multicolumnFields }}</td>
        <td>
          <button class="btn btn-primary" @click.prevent="insertNewMulticolumnField()">
            <i class="fa fa-plus"></i> {{ $t('Add') }}
          </button>
        </td>
      </tr>
    </tbody>
    <tbody v-else>
      <tr>
        <td colspan="4">
          <p>{{ $t('Unavailable') }}</p>
          <p>{{ $t('CSV file not selected') }}</p>
        </td>
      </tr>
    </tbody>
  </table>
</div>
</template>

<script>
import MultiCsvFieldsSelector from './MultiCsvFieldsSelector'
import DbFieldsSelect from './DbFieldsSelect'

import { mapVuexModels } from '@/helpers'

import { createNamespacedHelpers } from 'vuex'
const { mapGetters, mapActions } = createNamespacedHelpers('importer')

export default {
  components: {
    MultiCsvFieldsSelector,
    DbFieldsSelect
  },
  computed: {
    ...mapVuexModels([
      'multicolumnFields'
    ], 'importer'),
    ...mapGetters([
      'dbFields',
      'csvFields'
    ])
  },
  methods: {
    deleteMulticolumnField (idx) {
      this.multicolumnFields = this.multicolumnFields.filter((val, index) => index !== idx)
    },
    insertNewMulticolumnField () {
      this.multicolumnFields.push({})
    },
    ...mapActions([
      'store'
    ])
  }
}
</script>
