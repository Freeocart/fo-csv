<template>
  <div>
    <table class="table table-striped" v-if="statuses.length">
      <thead>
        <tr>
          <th>Статус</th>
          <th>Rewrite</th>
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
import StatusRewriteItem from '@/components/StatusRewritesItem'

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
  },
  created () {
    console.log(this.statuses, this.rules)
  }
}
</script>
