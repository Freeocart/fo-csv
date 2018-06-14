<template>
<div>
  <span class="input-group">
    <select v-model="value" @change="$emit('change', $event.target.value)" class="form-control" :placeholder="$t('Attribute value')">
      <option :value="null">{{ $t('Not selected') }}</option>
      <optgroup v-for="(group, groupKey) in dbFields" :key="groupKey" :label="groupKey">
        <option :value="(groupKey + ':' + dbField)" v-for="(dbField, idx) in group" :key="idx">{{ dbField }}</option>
      </optgroup>
    </select>
  </span>
</div>
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
  computed: {
    ...mapGetters([
      'dbFields'
    ])
  }
}
</script>
