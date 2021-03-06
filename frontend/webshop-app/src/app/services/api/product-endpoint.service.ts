import {Injectable} from '@angular/core';
import {NewProduct, Product} from '../../types/api/product';
import {ApiResourceEndPoint} from './api-resource-end-point';
import {HttpRequestorService} from './http-requestor.service';
import {ApiRequest, CommonEndPoints} from '../../types/api-request';
import {Observable} from 'rxjs';

@Injectable(
    {providedIn: 'root'}
)
export class ProductEndpointService extends ApiResourceEndPoint<Product, NewProduct> {

    private endpoints: CommonEndPoints = {
        all: {
            resource: 'product/',
            scope: ['anonymous'],
            method: 'GET',
        },
        byId: {
            resource: 'product/',
            scope: ['anonymous'],
            method: 'GET',
        },
        create: {
            resource: 'product/',
            scope: ['admin'],
            method: 'POST',
        }
    };

    constructor(protected requestor: HttpRequestorService) {
        super(requestor);
    }

    getEndPoints(): CommonEndPoints {
        return this.endpoints;
    }

    byCategorySearch(cat_id: number, search: string): Observable<Product[]> {
        if (search === '') {
            return this.requestor.request<Product[]>(
                {
                    resource: 'product/category/' + cat_id,
                    scope: ['anonymous'],
                    method: 'GET',
                    body: {}
                }
            );
        } else {
            return this.requestor.request<Product[]>(
                {
                    resource: `product/category/${cat_id}?search=${search}`,
                    scope: ['anonymous'],
                    method: 'GET',
                    body: {}
                }
            );
        }
    }

    update(product_id: number, product: NewProduct): Observable<null> {
        return this.requestor.request<null>({
            method: 'PUT',
            resource: 'product/' + product_id,
            scope: ['admin'],
            body: product
        });
    }
}
