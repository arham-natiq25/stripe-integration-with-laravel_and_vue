<template>
    <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>
    <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>

    <div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <b>
                        <label for="card-name">Cardholder's Name</label>
                    </b>
                    <input type="text" id="card-name" v-model="cardholderName" class="form-control " /> <br>
                </div>
            </div>

        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <b>
                        <label for="email">Email</label>
                    </b>
                    <input type="email" id="email" v-model="email" class="form-control" /><br>

                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">

                <div class="form-group">
                    <b>
                        <label for="card-number">Card Number</label>
                    </b>
                    <div id="card-number" class="form-control card-input"></div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-md-3">
                <b>
                    <label for="card-expiry">Expiration Date</label>
                </b>
                <div id="card-expiry" class="form-control card-input"></div>
            </div>
            <div class="col-md-3">
                <b>
                    <label for="card-cvc">CVC</label>
                </b>
                <div id="card-cvc" class="form-control card-input"></div>
            </div>
        </div>

    </div>

    <button class="btn btn-success mt-3" @click="submit">Submit</button>
    <button class="btn btn-danger mt-3" >Pay with saved Cards</button>

    <div>
        <div class="row">
            <div class="col-md-8">
                <b><label for="">Select Card</label></b>
                <select name="selectedCard" class="form-select" id="selectedCard" v-model="selectedCard">
                    <option :value="card" v-for="card in cards" :key="card.id">****-****-****-{{card.last_4_digits}}</option>
                </select>
            </div>
            <div class="col-md-3 mt-4">
                <button class="btn btn-success" @click="payWithCard">Pay</button>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props: [
        'user'
    ],
    data() {
        return {
            elements: null,
            stripe: null,
            cardNumberElement: null,
            cardExpiryElement: null,
            cardCvcElement: null,
            payment: 100,
            errorMessage: "",
            successMessage: "",
            cardholderName: "",
            email: "",
            user_id: null,



            cards:[],
            selectedCard:null,
        }
    },
    mounted() {
        this.loadStripe();
        this.getCards();
        this.user_id=this.user.id
    },
    methods: {
        loadStripe() {
            this.getCards();
            if (window.Stripe) {
                this.stripe = window.Stripe("pk_test_51NUU03Emu0Ala7lxKFLz0kgK8mfOVQr99wlJMIDW39xzneQ0B6Zb2x9irWjjNuldkUYyDFQG11FE50M6px3wvrVx00A0milkpo");
                this.elements = this.stripe.elements();
                // Create an instance of the card number Element
                this.cardNumberElement = this.elements.create("cardNumber", {
                    placeholder: "Card Number",
                });
                this.cardNumberElement.mount("#card-number");

                // Create an instance of the card expiry Element
                this.cardExpiryElement = this.elements.create("cardExpiry", {
                    placeholder: "MM / YY",
                });
                this.cardExpiryElement.mount("#card-expiry");

                // Create an instance of the card cvc Element
                this.cardCvcElement = this.elements.create("cardCvc", {
                    placeholder: "CVC",
                });
                this.cardCvcElement.mount("#card-cvc");
            } else {
                // Handle the case when Stripe is not available
                console.error("Stripe is not available");
            }
        },
        async submit() {
            this.errorMessage = "";
            const cardElement = this.elements.getElement("cardNumber");
            const { paymentMethod, error } = await this.stripe.createPaymentMethod({
                type: "card",
                card: cardElement,
                billing_details: {
                    name: this.cardholderName,
                    email: this.email,
                },
            });
            console.log("Error from Stripe:", error); // Log Stripe error
            if (error) {
                this.errorMessage = error.message;
            }
            else {
                this.processPayment(paymentMethod.id);
            }
            console.log("Final error message:", this.errorMessage); // Log final error message
        },
        async processPayment(paymentMethodId) {
            const response = await fetch("/api/customer/add-Member", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    paymentMethodId,
                    payment: this.payment,
                    name: this.cardholderName,
                    email: this.email,
                    user_id: this.user_id

                }),
            });
            const responseData = await response.json();
            if (responseData.success) {
                this.successMessage = responseData.message;
                setTimeout(() => {
                    this.successMessage = "";
                }, 10000);
            }
            else {
                this.errorMessage = responseData.message;

                // Hide error message after 10 seconds
                setTimeout(() => {
                    this.errorMessage = "";
                }, 10000);
            }

        },
        getCards() {
        axios.get('/api/cards',{
        }).then((res) => {
            this.cards = res.data.filter((item)=>item.user_id === this.user_id);

        }).catch((error) => {
            console.error('Error fetching cards:', error);
        });
    },
    payWithCard(){
     if (this.selectedCard==null) {
        this.errorMessage="Please Select Card"
     }else{
        axios.post('/api/customer/pay-with-card',this.selectedCard).then((res)=>{
            this.successMessage=res.data.message;
        }).catch(error=>{
            this.errorMessage=error;
        });
     }
    }


    }

}
</script>
<style>
/* Add any custom styles for the card inputs here */
.card-input {
    padding: 10px;
}
</style>
