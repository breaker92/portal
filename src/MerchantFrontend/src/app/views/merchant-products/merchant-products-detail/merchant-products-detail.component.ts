import {Component, OnDestroy, OnInit} from '@angular/core';
import {ActivatedRoute} from '@angular/router';
import {Product} from '../../../core/models/product.model';
import {Subscription} from 'rxjs';
import { MerchantApiService } from '../../../core/services/merchant-api.service';

@Component({
  selector: 'portal-merchant-products-detail',
  templateUrl: './merchant-products-detail.component.html',
  styleUrls: ['./merchant-products-detail.component.scss']
})
export class MerchantProductsDetailComponent implements OnInit, OnDestroy {

  product: Product;

  // Subscription
  private subResolver: Subscription;

  constructor(private activeRoute: ActivatedRoute, private merchantApiService: MerchantApiService) {
    // Get resolved product from route
    this.subResolver = this.activeRoute.data.subscribe(value => {
      console.log(value);
      this.product = value.product;
    });
  }

  ngOnInit(): void {
  }

  ngOnDestroy(): void {
    if (this.subResolver) {
      this.subResolver.unsubscribe();
    }
  }

  saveProduct() {

  }
}
