import { DataProvider } from 'react-admin';
import { getQuestionsAnalysis } from '../common/apiClient';

export const questionAnalysisDataProvider: DataProvider = {
    getList: async (resource, params) => {
        if (resource === 'question-analysis') {
            try {
                const result = await getQuestionsAnalysis(params);
                return {
                    data: result.data,
                    total: result.total,
                };
            } catch (error) {
                console.error('Error fetching questions analysis:', error);
                throw error;
            }
        }
        throw new Error(`Unsupported resource: ${resource}`);
    },

    getOne: async (resource, params) => {
        throw new Error(`getOne not implemented for ${resource}`);
    },

    getMany: async (resource, params) => {
        throw new Error(`getMany not implemented for ${resource}`);
    },

    getManyReference: async (resource, params) => {
        throw new Error(`getManyReference not implemented for ${resource}`);
    },

    create: async (resource, params) => {
        throw new Error(`create not implemented for ${resource}`);
    },

    update: async (resource, params) => {
        throw new Error(`update not implemented for ${resource}`);
    },

    updateMany: async (resource, params) => {
        throw new Error(`updateMany not implemented for ${resource}`);
    },

    delete: async (resource, params) => {
        throw new Error(`delete not implemented for ${resource}`);
    },

    deleteMany: async (resource, params) => {
        throw new Error(`deleteMany not implemented for ${resource}`);
    },
};
