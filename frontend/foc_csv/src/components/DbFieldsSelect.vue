<template>
  <select v-model="__selected">
    <optgroup v-for="(group, key) in data" :key="key" :label="key">
      <option :value="dbField" v-for="(dbField, idx) in group" :key="idx">{{ dbField }}</option>
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
