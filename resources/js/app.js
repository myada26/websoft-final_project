import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.data('transactionFlow', () => ({
    step: 1,
    student: null,
    feeProfiles: [],
    unpaidFines: [],
    selectedFines: [],
    searchResults: [],
    selectedFees: [],
    paymentMethod: 'CASH',
    gcashRef: '',
    remarks: '',
    studentSearch: '',

    init() {
        this.feeProfiles = this.readJson('transaction-fee-profiles', []);
        this.unpaidFines = this.readJson('transaction-unpaid-fines', []);
        this.searchResults = this.readJson('transaction-search-results', []);
        this.studentSearch = this.readJson('transaction-search-query', '');

        document.querySelectorAll('.fine-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => this.handleFineChange());
        });
    },

    handleFineChange() {
        const checked = document.querySelectorAll('.fine-checkbox:checked');

        if (this.selectedFees.length > 0 && checked.length > 0) {
            checked.forEach(cb => cb.checked = false);
            alert('Please select either a fee or a fine, not both. Process them as separate transactions.');
            this.selectedFines = [];
            return;
        }

        this.selectedFines = Array.from(checked).map(cb => {
            const fine = this.unpaidFines.find(f => f.id === Number(cb.value));
            return fine ? { id: cb.value, ...fine } : null;
        }).filter(f => f !== null);
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
        if (this.selectedFines.length > 0) {
            alert('Please select either a fee or a fine, not both. Process them as separate transactions.');
            return;
        }

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
        const feesTotal = this.selectedFees.reduce((sum, fee) => sum + Number.parseFloat(fee.amount || 0), 0);
        const finesTotal = this.selectedFines.reduce((sum, fine) => sum + Number.parseFloat(fine.amount || 0), 0);
        return feesTotal + finesTotal;
    },

    canProceedStep1() {
        return !!this.student;
    },

    canProceedStep2() {
        const hasFee = this.selectedFees.length === 1;
        const hasFine = this.selectedFines.length > 0;
        return hasFee || hasFine;
    },

    canProceedStep3() {
        return this.paymentMethod && (this.paymentMethod !== 'GCASH' || this.gcashRef.trim().length > 0);
    },

    hasSelectedFine() {
        return this.selectedFines.length > 0;
    },

    selectedFineIds() {
        return this.selectedFines.map(f => f.id);
    },

    resetFlow() {
        this.step = 1;
        this.student = null;
        this.selectedFees = [];
        this.selectedFines = [];
        this.paymentMethod = 'CASH';
        this.gcashRef = '';
        this.remarks = '';
    },
}));

Alpine.start();
