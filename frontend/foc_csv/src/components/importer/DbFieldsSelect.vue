<template>
  <select v-model="__selected" class="form-control">
    <option :value="null">{{ $t('Not selected') }}</option>
    <optgroup v-for="(group, groupKey) in data" :key="groupKey" :label="groupKey">
      <option :value="(groupKey + ':' + dbField)" v-for="(dbField, idx) in group" :key="idx">{{ dbField }}</option>
    </optgroup>
  </select>
</template>

<script>
export default {
  props: {
    data: {
      type: Object,
      default: () => ({})
    },
    value: {
      default: null
    }
  },
  computed: {
    __selected: {
      get () {
        return this.value
      },
      set (value) {
        this.$emit('input', value)
      }
    }
  }
}
</script>
