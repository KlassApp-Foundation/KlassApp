<template>
  <card class="w-full flex flex-col items-center justify-center">
    <div class="px-3 py-3">
      <h1 class="text-2xl text-80 font-light mb-4">Subscription Details</h1>
      <table class="w-full">
        <thead class="bg-grey-light">
          <tr class="border-t-2 border-b-2">
            <th class="text-left text-sm px-2 py-2 text-grey-darker">School Name</th>
            <th class="text-left text-sm px-2 py-2 text-grey-darker">Plan Name</th>
            <th class="text-left text-sm px-2 py-2 text-grey-darker">Plan</th>
            <th class="text-left text-sm px-2 py-2 text-grey-darker">Amount</th>
            <th class="text-left text-sm px-2 py-2 text-grey-darker">Expired Date</th>
          </tr>
        </thead>
        <tbody class="bg-grey-light" v-if="this.subscriptions != ''">
          <tr class="border-t-2 border-b-2" v-for="subscription in subscriptions">
            <td class="py-3 px-2">{{ subscription['school'] }}</td>
            <td class="py-3 px-2">{{ subscription['plan'] }}</td>
            <td class="py-3 px-2">{{ subscription['plan_days'] }}days</td>
            <td class="py-3 px-2">{{ subscription['amount'] }}</td>
            <td class="py-3 px-2">{{ subscription['end_date'] }}</td>
          </tr>
        </tbody>
        <tbody v-if="this.subscriptions == ''">
          <tr class="border-b">
            <td colspan="5">
              <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
            </td>
          </tr>
        </tbody>
      </table>
      <div class="my-6">
        <a :href="'/portal/resources/subscriptions'">View All</a>
      </div>
    </div>
  </card>
</template>

<script>
export default {
  props: [
    "card"

    // The following props are only available on resource detail cards...
    // 'resource',
    // 'resourceId',
    // 'resourceName',
  ],
  data() {
    return {
      subscriptions: []
    };
  },
  methods: {
    getData() {
      axios.get("/payment/subscription/").then(response => {
        this.subscriptions = response.data.data;
      });
    }
  },

  created() {
    this.getData();
  },

  mounted() {
    //console.log("I am mounted")
  }
};
</script>
