<template>
  <div>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>{{ $t('DB field') }}</th>
          <th>{{ $t('CSV header') }}</th>
          <th>{{ $t('Control') }}</th>
        </tr>
      </thead>
      <tbody>
        <template v-if="bindings.length > 0">
          <tr v-for="(binding, idx) in bindings" :key="idx">
            <td>
              <select v-model="binding.dbField" @change="setDefaultCSVHeader(binding, $event.target.value)" class="form-control">
                <option :value="null">{{ $t('Not selected') }}</option>
                <optgroup v-for="(group, groupKey) in dbFields" :key="groupKey" :label="groupKey">
                  <option :value="(groupKey + ':' + dbField)" v-for="(dbField, idx) in group" :key="idx">{{ dbField }}</option>
                </optgroup>
              </select>
            </td>
            <td>
              <input type="text" class="form-control" :placeholder="$t('CSV header')" v-model="binding.header">
            </td>
            <td class="text-center">
              <button class="btn btn-danger" @click.prevent="removeBinding(binding)"><i class="fa fa-times"></i></button>
            </td>
          </tr>
        </template>
        <tr v-else>
          <td colspan=3>
            <span>
              {{ $t('There is no created bindings') }}
            </span>
          </td>
        </tr>
      </tbody>
    </table>

    <button class="btn btn-primary" @click.prevent="addBinding()"><i class="fa fa-plus"></i> {{ $t('Add new db field binding') }}</button>

  </div>
</template>

<script>
import { createNamespacedHelpers } from 'vuex'

const { mapGetters } = createNamespacedHelpers('exporter')

export default {
  props: {
    value: {
      type: Array,
      default: () => []
    }
  },
  computed: {
    bindings: {
      get () {
        return this.value || []
      },
      set (val) {
        this.$emit('input', val)
      }
    },
    ...mapGetters([
      'dbFields'
    ])
  },
  methods: {
    addBinding () {
      this.bindings.push({})
    },
    removeBinding (binding) {
      this.bindings = this.bindings.filter(item => item !== binding)
    },
    setDefaultCSVHeader (binding, value) {
      if (!binding.header) {
        binding.header = value
      }
    }
  }
  // watch: {
  //   bindings (value) {
  //     this.$emit('input', value)
  //   }
  // }
}
</script>
