<template>
<div>
  <label class="label label-default">{{ $t('Multicolumn fields settings') }}</label>
  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>{{ $t('Template variables') }}</th>
        <th>{{ $t('Preprocess value template') }}</th>
        <th>{{ $t('DB field') }}</th>
        <th>{{ $t('Mode') }}</th>
        <th>{{ $t('Control') }}</th>
      </tr>
    </thead>
    <tbody v-if="csvFields && csvFields.length > 0">
      <tr v-for="(field, idx) in multicolumnFields" :key="idx">
        <td>
          <div class="b-variable-group-definition" v-for="(group, groupIdx) in field.csvFields" :key="groupIdx">
            <label class="label label-default">
              {{ $t('Template variable name') }}
            </label>
            <input class="form-control" type="text" v-model="group.variable">

            <multi-csv-fields-selector :options="csvFields" v-model="group.fields"></multi-csv-fields-selector>

            <button @click.prevent="deleteMulticolumnFieldGroup(idx, groupIdx)" class="btn btn-danger btn-block">
              <i class="fa fa-times"></i> {{ $t('Remove template variable') }}
            </button>
          </div>
          <button @click.prevent="insertNewMulticolumnFieldGroup(idx)" class="btn btn-primary">
            <i class="fa fa-plus"></i> {{ $t('Add new template variable') }}
          </button>
        </td>
        <td>
          <textarea v-model="field.valueTemplate" class="form-control b-form-control-fill-height" :placeholder="$t('Preprocess value template')">
          </textarea>
        </td>
        <td>
          <db-fields-select :data="dbFields" v-model="field.dbField"></db-fields-select>
        </td>
        <td>
          <select v-model="field.mode" class="form-control">
            <option value="replace">{{ $t('Replace CSV field data') }}</option>
            <option value="after">{{ $t('Add after CSV field data') }}</option>
            <option value="before">{{ $t('Add before CSV field data') }}</option>
          </select>
        </td>
        <td>
          <button class="btn btn-danger" @click.prevent="deleteMulticolumnField(idx)">
            <i class="fa fa-times"></i> {{ $t('Delete') }}
          </button>
        </td>
      </tr>
      <tr>
        <td colspan="4">&nbsp;</td>
        <td>
          <button class="btn btn-primary" @click.prevent="insertNewMulticolumnField()">
            <i class="fa fa-plus"></i> {{ $t('Add') }}
          </button>
        </td>
      </tr>
    </tbody>
    <tbody v-else>
      <tr>
        <td colspan="5">
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

import { mapVuexModels } from 'vuex-models'

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
      this.multicolumnFields.push({
        csvFields: [],
        mode: 'replace',
        dbField: 'null'
      })
    },
    deleteMulticolumnFieldGroup (idx, groupIdx) {
      this.multicolumnFields[idx].csvFields = this.multicolumnFields[idx].csvFields.filter((val, index) => index !== idx)
    },
    insertNewMulticolumnFieldGroup (idx) {
      let group = {
        variable: `source_${this.multicolumnFields[idx].csvFields.length}`,
        fields: []
      }
      this.multicolumnFields[idx].csvFields.push(group)
    },
    ...mapActions([
      'store'
    ])
  }
}
</script>

<style scoped>
.b-variable-group-definition {
  padding: .5em;
  border: 1px solid #ddd;
  margin: .25em 0;
}
.b-form-control-fill-height {
  resize: vertical;
  min-width: 300px;
}
</style>
