// Estructuras vacías de los documentos de un perfil (deben coincidir con lo que espera ProfileController)

export function emptyDetailLine() {
    return {
        type: null,
        percentage: null,
        account: null,
        exento: 0,
        type_calculation: null,
        calculation: false,
        for_delete: 0,
    };
}

export function emptyFieldLine() {
    return {
        account: null,
        name: null,
        type_calculation: null,
        for_delete: 0,
    };
}

export function emptyDocument() {
    return {
        name: null,
        type_document_sap: null,
        type_calculation: null,
        ice: 0,
        tasas: 0,
        exento: 0,
        ice_status: false,
        tasas_status: false,
        exento_status: false,
        authorization_number_status: false,
        cuf_status: false,
        control_code_status: false,
        business_name_status: false,
        nit_status: false,
        discount_status: false,
        gift_card_status: false,
        rate_zero_status: false,
        detail: [emptyDetailLine()],
        fields: [],
        for_delete: 0,
    };
}

// Etiqueta legible por código de cuenta, recorriendo el árbol de cuentas SAP
export function buildAccountLabels(nodes, map = {}) {
    for (const node of nodes || []) {
        map[node.AcctCode] = node.FormatCode ? `${node.FormatCode} - ${node.AcctName}` : node.AcctName;
        if (node.children?.length) buildAccountLabels(node.children, map);
    }
    return map;
}

// Deja solo las hojas del árbol cuyo LocManTran coincide (cuentas asociadas o no a socio de negocio)
export function filterTreeByLocManTran(nodes, locManTran) {
    return nodes.reduce((acc, node) => {
        const children = node.children?.length ? filterTreeByLocManTran(node.children, locManTran) : [];
        if (children.length > 0) {
            acc.push({ ...node, children });
        } else if (!node.children?.length && node.LocManTran === locManTran) {
            acc.push(node);
        }
        return acc;
    }, []);
}

// Campos de validación agrupados por pestaña del formulario de perfil
export const profileSections = {
    general: ["name", "type_currency"],
    documents: ["documents."],
};
