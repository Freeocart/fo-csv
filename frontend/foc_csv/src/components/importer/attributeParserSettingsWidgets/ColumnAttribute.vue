<template>
  <ul>
    <li v-for="(binding, idx) in bindings" :key="idx">
      <select v-model="binding.field" @change="setDefaultBindingName(binding, csvFields[$event.target.value])">
        <option v-for="(csvField, idx) in csvFields" :key="idx" :value="idx">{{ csvField }}</option>
      </select>

      <input type="text" v-model="binding.name" placeholder="name">

      <button @click.prevent="removeBinding(binding)"><i class="fa fa-cross"></i> X</button>
    </li>
    <li>
      <button @click.prevent="addBinding">add</button>
    </li>
  </ul>
</template>

<script>
import { createNamespacedHelpers } from 'vuex'

const { mapGetters } = createNamespacedHelpers('importer')

export default {
  props: {
    value: {
      default: ''
    }
  },
  data () {
    let bindings = []

    try {
      bindings = JSON.parse(this.value)
    }
    catch (e) {
      console.error(e)
      bindings = []
    }

    return {
      bindings
    }
  },
  computed: {
    ...mapGetters([
      'csvFields'
    ])
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
      this.$emit('change', JSON.stringify(newV))
    }
  }
}
</script>
