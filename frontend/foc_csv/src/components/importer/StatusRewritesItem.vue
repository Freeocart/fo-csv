<template>
<tr>
  <td>
    {{ name }}
  </td>
  <td>
    <input type="text" v-model="itemValue" class="form-control">
  </td>
</tr>
</template>

<script>
export default {
  props: {
    id: {
      default: 0
    },
    name: {
      default: ''
    },
    val: {
      default: ''
    }
  },
  computed: {
    itemValue: {
      get () {
        return this.val
      },
      set (val) {
        this.$emit('setRewriteRuleValue', {
          id: this.id,
          val
        })
      }
    }
  }
}
</script>
