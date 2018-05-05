<template>
  <div>
    <table class="table table-striped" v-if="statuses.length">
      <thead>
        <tr>
          <th>{{ $t('Opencart status') }}</th>
          <th>{{ $t('CSV status') }}</th>
        </tr>
      </thead>
      <tbody>
        <status-rewrite-item
          v-for="(status, idx) in statuses"
          :key="idx"
          :name="status.name"
          :id="idx"
          :val="rules[idx] || ''"
          @setRewriteRuleValue="setRewriteRule($event)"
        >
        </status-rewrite-item>
      </tbody>
    </table>
  </div>
</template>

<script>
import StatusRewriteItem from './StatusRewritesItem'

export default {
  components: {
    StatusRewriteItem
  },
  props: {
    statuses: {},
    rules: {}
  },
  methods: {
    setRewriteRule (data) {
      this.$emit('statusRewriteChange', data)
    }
  }
}
</script>
