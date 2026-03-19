<template>
<div>
 <vue-good-table
  :columns="columns"
  :rows="rows"
  :search-options="{
    enabled: true,
    skipDiacritics: true,
    placeholder: 'Search this table'
  }">
</vue-good-table> 
         
</div>
</template>
<script>
import 'vue-good-table/dist/vue-good-table.css'
import { VueGoodTable } from 'vue-good-table';
export default {

 components: {
  VueGoodTable,
},
  data() {
    return{
      columns: [
        {
          label: 'Book Code',
          field: 'book_code_no',
         
          
        },
        {
          label: 'Book Name',
          field: 'title',
         
        },
        {
          label: 'ISBN',
          field: 'isbn_number',
         
        },
        {
          label: 'Lent Date',
          field: 'issue_date',
        },

         {
          label: 'Return Date',
          field: 'return_date',
        },
      
      ],
      rows: [
     
       
      ],
    };
  },

  created()
  {
    //alert('sfdfsd');
    this.getData();
  },
  methods: {

    getData()
    {
      axios.get("/student/libraryactivity/show").then(response => {

        this.rows = response.data.data;
          
      });
    },


  },


};
</script>
