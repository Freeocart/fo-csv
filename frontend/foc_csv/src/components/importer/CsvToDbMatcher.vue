<template>
<div>
  <label for="" class="label label-default">{{ $t('Fields matching') }}</label>

  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>{{ $t('CSV field') }}</th>
        <th>{{ $t('DB field') }}</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="(field, idx) in csvFields" :key="idx">
        <td>
          <span>{{ field }}</span>
        </td>
        <td>
          <db-fields-select :track-old-values="true" :value="findSelected(idx)" :data="dbFields" @input="addBinding($event, idx)" ></db-fields-select>
        </td>
      </tr>
    </tbody>
  </table>

  <button @click.prevent="setBindings({})" class="btn btn-danger"><i class="fa fa-trash"></i> {{ $t('Reset db field bindings') }}</button>
</div>
</template>

<script>
import { createNamespacedHelpers } from 'vuex'
import DbFieldsSelect from './DbFieldsSelect'

const { mapGetters } = createNamespacedHelpers('importer')

export default {
  components: {
    DbFieldsSelect
  },
  props: {
    // [db -> csv, ...]
    value: {
      type: Object,
      default: () => ({})
    }
  },
  computed: {
    ...mapGetters([
      'currentProfile',
      'csvFields',
      'dbFields'
    ])
  },
  methods: {
    addBinding ({ newValue, oldValue }, value) {
      // remove old binding
      const { [oldValue]: removedValue, ...cleanedValue } = this.value

      if (newValue) {
        this.setBindings({ ...cleanedValue, [newValue]: value })
      }
      else {
        this.setBindings({ ...cleanedValue })
      }
    },
    setBindings (bindings) {
      this.$emit('input', bindings)
    },
    findSelected (idx) {
      return Object.keys(this.value).reduce((prev, key) => this.value[key] === idx ? key : prev, null)
    }
  }
}
</script>
