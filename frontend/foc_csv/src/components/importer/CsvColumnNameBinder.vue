<template>
<div>
  <ul class="list-group">
    <li v-for="(binding, idx) in bindings" :key="idx" class="list-group-item">
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

export default {
  props: {
    value: {
      default: ''
    },
    items: {
      type: Array,
      default: () => ([])
    }
  },
  data () {
    let bindings = []

    try {
      bindings = JSON.parse(this.value)
    }
    catch (e) {
      bindings = []
    }

    return {
      bindings
    }
  },
  methods: {
    addBinding () {
      this.bindings.push({})
    },
    removeBinding (binding) {
      this.bindings = this.bindings.filter(item => item !== binding)
    },
    setDefaultBindingName (binding, value) {
      if (!binding.name) {
        binding.name = value
      }
    }
  },
  watch: {
    bindings (newV) {
      this.$emit('input', JSON.stringify(newV))
    }
  }
}
</script>
