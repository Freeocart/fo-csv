<template>
<div>
  <ul class="list-group">
    <li v-for="(binding, idx) in _value" :key="idx" class="list-group-item">
      <span class="input-group">
        <select v-model="binding.field" @change="setDefaultBindingName(binding, items[$event.target.value])" class="form-control" :placeholder="$t('Attribute value')">
          <option :value="undefined">{{ $t('Not selected') }}</option>
          <option v-for="(csvField, idx) in items" :key="idx" :value="idx">{{ csvField }}</option>
        </select>

        <input type="text" v-model="binding.name" :placeholder="$t('Attribute name')" class="form-control">

        <span class="input-group-btn">
          <button @click.prevent="removeBinding(binding)" class="btn btn-danger btn-sm"><i class="fa fa-times"></i></button>
        </span>
      </span>
    </li>
  </ul>
  <button @click.prevent="addBinding" class="btn btn-primary btn-block"><i class="fa fa-plus"></i> {{ $t('Add new column binding') }}</button>
</div>
</template>

<script>
import Vue from 'vue'

export default {
  props: {
    value: {
      type: Array,
      default: () => ([])
    },
    items: {
      type: Array,
      default: () => ([])
    }
  },
  computed: {
    _value: {
      get () {
        return this.value
      },
      set (val) {
        this.$emit('input', val)
      }
    }
  },
  methods: {
    addBinding () {
      this._value.push({})
    },
    removeBinding (binding) {
      this._value = this._value.filter(item => item !== binding)
    },
    setDefaultBindingName (binding, value) {
      if (!binding.name) {
        Vue.set(binding, 'name', value)
      }
    }
  }
}
</script>
