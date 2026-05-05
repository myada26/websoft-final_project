import Alpine from 'alpinejs';
window.Alpine = Alpine;

Alpine.data('transactionFlow', () => ({
    step: 1,
    student: null,
    feeProfiles: [],
    searchResults: [],
    selectedFees: [],
    paymentMethod: 'CASH',
    gcashRef: '',
    remarks: '',
    studentSearch: '',

    init() {
        this.feeProfiles = this.readJson('transaction-fee-profiles', []);
        this.searchResults = this.readJson('transaction-search-results', []);
        this.studentSearch = this.readJson('transaction-search-query', '');
    },

    readJson(id, fallback) {
        const element = document.getElementById(id);
        if (!element) return fallback;

        try {
            return JSON.parse(element.textContent || 'null') ?? fallback;
        } catch {
            return fallback;
        }
    },

    searchStudent() {
        const query = this.studentSearch.trim();
        if (!query) return;

        const url = new URL(window.location.href);
        url.searchParams.set('student', query);
        window.location.href = url.toString();
    },

    chooseStudent(id) {
        const student = this.searchResults.find((item) => Number(item.id) === Number(id));
        if (!student) return;

        this.student = student;
        this.step = 2;
    },

    feeById(id) {
        return this.feeProfiles.find((fee) => Number(fee.id) === Number(id));
    },

    toggleFee(id) {
        const fee = this.feeById(id);
        if (!fee) return;

        if (this.hasFee(id)) {
            this.selectedFees = [];
            return;
        }

        this.selectedFees = [fee];
    },

    hasFee(id) {
        return this.selectedFees.some((fee) => Number(fee.id) === Number(id));
    },

    totalAmount() {
        return this.selectedFees.reduce((sum, fee) => sum + Number.parseFloat(fee.amount || 0), 0);
    },

    canProceedStep1() {
        return !!this.student;
    },

    canProceedStep2() {
        return this.selectedFees.length === 1;
    },

    canProceedStep3() {
        return this.paymentMethod && (this.paymentMethod !== 'GCASH' || this.gcashRef.trim().length > 0);
    },

    resetFlow() {
        this.step = 1;
        this.student = null;
        this.selectedFees = [];
        this.paymentMethod = 'CASH';
        this.gcashRef = '';
        this.remarks = '';
    },
}));

Alpine.start();
