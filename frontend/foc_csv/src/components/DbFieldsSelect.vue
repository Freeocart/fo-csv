<template>
  <select v-model="__selected">
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
      defaults: {}
    },
    selected: {
      defaults: ''
    }
  },
  computed: {
    __selected: {
      get () {
        return this.selected
      },
      set (value) {
        this.$emit('changed', value)
      }
    }
  },
  methods: {
    change (value) {
      this.$emit('changed', value)
    }
  }
}
</script>
