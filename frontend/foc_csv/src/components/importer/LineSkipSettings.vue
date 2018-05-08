<template>
<div>
  <label class="label label-default">{{ $t('Skip line if fields empty') }}</label>

  <ul class="list-group">
    <li v-for="(item, idx) in value" :key="idx" class="list-group-item clearfix">
      <strong class="pull-left">{{ item.name }}</strong>
      <span class="pull-right">
        <button class="btn btn-danger btn-xs" @click.prevent="removeSkipField(item.idx)"><i class="fa fa-times"></i></button>
      </span>
    </li>
  </ul>

  <hr>
  <div>
    <select v-model="newSkipField" class="form-control">
      <option :value="undefined">{{ $t('Not selected') }}</option>
      <option v-for="(field, csvIdx) in csvFields" :key="csvIdx" :disabled="alreadySelected(csvIdx)" :value="{ idx: csvIdx, name: field }">{{ field }}</option>
    </select>
  </div>
  <hr>

</div>
</template>

<script>
export default {
  props: {
    csvFields: {
      default: []
    },
    value: {
      default: []
    }
  },
  computed: {
    newSkipField: {
      get () {
        return undefined
      },
      set (val) {
        this.addSkipField(val)
      }
    }
  },
  methods: {
    alreadySelected (idx) {
      return this.findIndex(idx) !== -1
    },
    findIndex (idx) {
      return this.value.findIndex(val => {
        return val.idx === idx
      })
    },
    removeSkipField (idx) {
      this.$emit('input', this.value.filter(item => item.idx !== idx))
    },
    addSkipField ({ idx, name }) {
      if (this.findIndex(idx) === -1) {
        let value = this.value.filter(() => true)
        value.push({
          idx,
          name
        })

        this.$emit('input', value)
      }
    }
  }
}
</script>
